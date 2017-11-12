<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Model;


use Akeeba\RemoteCLI\Api\Api;
use Akeeba\RemoteCLI\Api\Options;
use Akeeba\RemoteCLI\Download\Download as Fetcher;
use Akeeba\RemoteCLI\Exception\CannotDeleteFiles;
use Akeeba\RemoteCLI\Exception\CannotDownloadFile;
use Akeeba\RemoteCLI\Exception\CannotWriteFile;
use Akeeba\RemoteCLI\Exception\NoBackupID;
use Akeeba\RemoteCLI\Exception\NoDownloadMode;
use Akeeba\RemoteCLI\Exception\NoDownloadPath;
use Akeeba\RemoteCLI\Exception\NoDownloadURL;
use Akeeba\RemoteCLI\Exception\NoFilesInBackupRecord;
use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Output\Output;
use Exception;

class Download
{
	/**
	 * Validations the input and returns an array of download parameters
	 *
	 * @param   Cli  $input  The user input
	 *
	 * @return  array
	 */
	public function getValidatedParameters(Cli $input)
	{
		$parameters = [
			'mode'     => strtolower($input->getCmd('dlmode', 'http')),
			'path'     => $input->getString('dlpath', getcwd()),
			'id'       => $input->getInt('id', 0),
			'filename' => $input->getString('filename', ''),
			'delete'   => $input->getBool('delete', false),
		];

		if (!in_array($parameters['mode'], array('http', 'curl', 'chunk')))
		{
			throw new NoDownloadMode();
		}

		if (empty($parameters['path']) || !is_dir($parameters['path']))
		{
			throw new NoDownloadPath();
		}

		switch ($parameters['mode'])
		{
			case 'http':
				break;

			case 'chunk':
				$parameters['chunkSize'] = $input->getInt('chunk_size', 1);

				if ($parameters['chunkSize'] <= 1)
				{
					$parameters['chunkSize'] = 10;
				}
				break;

			case 'curl':
				$parameters['url'] = $input->get('dlurl', '', 'raw');
				$parameters['url'] = rtrim($parameters['url'], '/');

				if (empty($parameters['url']))
				{
					throw new NoDownloadURL();
				}

				list($parameters['url'], $parameters['authentication']) = $this->processAuthenticatedUrl($parameters['url']);
				break;
		}

		return $parameters;
	}

	/**
	 * Download a backup archive. If
	 *
	 * @param   array    $params   Download parameters
	 * @param   Output   $output   Output handler
	 * @param   Options  $options  API options
	 */
	public function download(array $params, Output $output, Options $options)
	{
		/**
		 * We check the Download ID late in the process since using backup + download means that we do not have access
		 * to the ID until we finish the backup. However, we have to make sure that the download information is correct
		 * before we take the backup to prevent wasting our time.
		 */
		if (($params['id'] <= 0))
		{
			throw new NoBackupID();
		}

		switch ($params['mode'])
		{
			case 'http':
				$this->downloadHTTP($params, $output, $options);
				break;

			case 'chunk':
				$this->downloadChunk($params, $output, $options);
				break;

			case 'curl':
				$this->downloadCURL($params, $output, $options);
				break;
		}

		$output->header("Finished downloading the backup archive");
	}

	/**
	 * Process a URL, extracting its authentication part as a separate string. Used for downloading with cURL.
	 *
	 * @param   string  $url  The URL to process e.g. "ftp://user:password@ftp.example.com/path/to/file.jpa"
	 *
	 * @return  array  [$url, $authentication]
	 */
	private function processAuthenticatedUrl($url)
	{
		$url                 = rtrim($url, '/');
		$authentication      = '';
		$doubleSlashPosition = strpos($url, '//');

		if ($doubleSlashPosition == false)
		{
			return array($url, $authentication);
		}

		$offset         = $doubleSlashPosition + 2;
		$atSignPosition = strpos($url, '@', $offset);
		$colonPosition  = strpos($url, ':', $offset);

		if (($colonPosition === false) || ($atSignPosition === false))
		{
			return array($url, $authentication);
		}

		$offset = $colonPosition + 1;

		while ($atSignPosition !== false)
		{
			$atSignPosition = strpos($url, '@', $offset);

			if ($atSignPosition !== false)
			{
				$offset = $atSignPosition + 1;
			}
		}

		$atSignPosition = $offset - 1;
		$authentication = substr($url, $doubleSlashPosition + 2, $atSignPosition - $doubleSlashPosition - 2);
		$protocol       = substr($url, 0, $doubleSlashPosition + 2);
		$restOfURL      = substr($url, $atSignPosition + 1);
		$url            = $protocol . $restOfURL;

		return array($url, $authentication);
	}

	/**
	 * Download a backup archive using a single chunk HTTP download through the JSON API.
	 *
	 * @param   array    $params   The download parameters, as determined by getValidatedParameters
	 * @param   Output   $output   The output handler object
	 * @param   Options  $options  API options
	 *
	 * @return  void
	 */
	private function downloadHTTP(array $params, Output $output, Options $options)
	{
		// Get the backup info
		list(, $parts, $fileInformation) = $this->getBackupArchiveInformation($params, $output, $options);

		$api  = new Api($options, $output);
		$path = $params['path'];

		for ($part = 1; $part <= $parts; $part++)
		{
			// Open file pointer
			$name     = $fileInformation[$part]->name;
			$size     = $fileInformation[$part]->size;
			$filePath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($filePath, 'wb');

			if ($fp == false)
			{
				throw new CannotWriteFile($filePath);
			}

			try
			{
				// Get the signed URL
				$url = $api->getURL('downloadDirect', [
					'backup_id' => $params['id'],
					'part_id'   => $part,
				], true);

				$fetcher = new Fetcher();

				switch (strtolower($fetcher->getAdapterName()))
				{
					case 'curl':
						$fetcher->setAdapterOptions([
							CURLOPT_CAINFO => $options->capath,
						]);
						break;

					case 'fopen':
						$fetcher->setAdapterOptions([
							'ssl' => [
								'cafile'       => $options->capath,
							],
						]);
						break;
				}

				$fetcher->getFromURL($url, true, $fp);
			}
			catch (Exception $e)
			{
				// Close the file pointer before re-throwing the exception
				fclose($fp);

				throw new CannotDownloadFile(sprintf("Could not download file ‘%s’ -- Network error “%s”", $filePath, $e->getMessage()), 105, $e);
			}

			// Check file size
			clearstatcache();
			$sizematch = true;
			$filesize = @filesize($filePath);

			if (($filesize !== false) && ($filesize != $size))
			{
				$output->warning(sprintf("Filesize mismatch on %s", $filePath));

				$sizematch = false;
			}

			if ($sizematch)
			{
				$filename = $params['filename'];

				// Try renaming
				if (strlen($filename))
				{
					@rename($filePath, $path . DIRECTORY_SEPARATOR . $filename);

					if (file_exists($path . DIRECTORY_SEPARATOR . $filename))
					{
						$output->info(sprintf("Successfully renamed %s to %s", $name, $filename));
					}
					else
					{
						$output->info(sprintf("Failed to rename %s to %s", $name, $filename));
					}
				}

				$output->info(sprintf("Successfully downloaded %s", $name), true);
			}
		}
	}

	/**
	 * Download a backup archive using multiple chunk HTTP download through the JSON API. Recommended for larger archives.
	 *
	 * @param   array    $params   The download parameters, as determined by getValidatedParameters
	 * @param   Output   $output   The output handler object
	 * @param   Options  $options  API options
	 *
	 * @return  void
	 */
	private function downloadChunk(array $params, Output $output, Options $options)
	{
		// Get the backup info
		list(, $parts, $fileInformation) = $this->getBackupArchiveInformation($params, $output, $options);

		$api  = new Api($options, $output);
		$path = $params['path'];
		$chunk_size = $params['chunkSize'];

		for ($part = 1; $part <= $parts; $part++)
		{
			// Open file pointer
			$name     = $fileInformation[$part]->name;
			$size     = $fileInformation[$part]->size;
			$filePath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($filePath, 'wb');

			if ($fp == false)
			{
				throw new CannotWriteFile($filePath);
			}

			$frag = 0;
			$done = false;

			while ( !$done)
			{
				$data = $api->doQuery('download', [
					'backup_id'  => $params['id'],
					'part'       => $part,
					'segment'    => ++$frag,
					'chunk_size' => $chunk_size
				]);

				switch ($data->body->status)
				{
					case 200:
						$rawData = base64_decode($data->body->data);
						$len     = strlen($rawData); //echo "\tWriting $len bytes\n";
						$output->debug(sprintf('Writing a chunk of %d bytes', $len));
						fwrite($fp, $rawData);
						unset($rawData);
						unset($data);
						break;

					case 404:
						if ($frag == 1)
						{
							throw new NoFilesInBackupRecord($params['id']);
						}

						$done = true;

						break;

					default:
						throw new CannotDownloadFile(sprintf("Could not download chunk #%02u of file ‘%s’ -- Remote API error %d : %s", $frag, $filePath, $data->body->status, $data->body->data));
						break;
				}
			}

			@fclose($fp);

			// Check file size
			clearstatcache();
			$sizematch = true;
			$filesize = @filesize($filePath);

			if (($filesize !== false) && ($filesize != $size))
			{
				$output->warning(sprintf("Filesize mismatch on %s", $filePath));

				$sizematch = false;
			}

			if ($sizematch)
			{
				$filename = $params['filename'];

				// Try renaming
				if (strlen($filename))
				{
					@rename($filePath, $path . DIRECTORY_SEPARATOR . $filename);

					if (file_exists($path . DIRECTORY_SEPARATOR . $filename))
					{
						$output->info(sprintf("Successfully renamed %s to %s", $name, $filename));
					}
					else
					{
						$output->info(sprintf("Failed to rename %s to %s", $name, $filename));
					}
				}

				$output->info(sprintf("Successfully downloaded %s", $name), true);
			}
		}

	}

	/**
	 * Download a backup archive using cURL, bypassing the JSON API. This is useful when you have (S)FTP or WebDAV access to the
	 * location of your backup archives.
	 *
	 * @param   array    $params   The download parameters, as determined by getValidatedParameters
	 * @param   Output   $output   The output handler object
	 * @param   Options  $options  API options
	 *
	 * @return  void
	 */
	private function downloadCURL(array $params, Output $output, Options $options)
	{
		// Get the backup info
		list(, $parts, $fileInformation) = $this->getBackupArchiveInformation($params, $output, $options);

		$path = $params['path'];
		$url = $params['url'];
		$authentication = $params['authentication'];

		for ($part = 1; $part <= $parts; $part++)
		{
			// Open file pointer
			$name     = $fileInformation[$part]->name;
			$size     = $fileInformation[$part]->size;
			$filePath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($filePath, 'wb');

			if ($fp == false)
			{
				throw new CannotWriteFile($filePath);
			}

			// Get the target path
			$url = $url . '/' . $name;

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
			//curl_setopt($ch, CURLOPT_TIMEOUT, 180);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0.1) Gecko/20110506 Firefox/4.0.1');
			curl_setopt($ch, CURLOPT_CAINFO, $options->capath);

			if ( !empty($authentication))
			{
				curl_setopt($ch, CURLOPT_USERPWD, $authentication);
			}

			$status = curl_exec($ch);

			@fclose($fp);

			$errno      = curl_errno($ch);
			$errmessage = curl_error($ch);

			curl_close($ch);

			if ($errno !== 0)
			{
				throw new CannotDownloadFile(sprintf("Could not download ‘%s’ over cURL -- cURL error #%d : %s", $filePath, $errno, $errmessage));
			}

			if ($status === false)
			{
				throw new NoFilesInBackupRecord($params['id']);
			}

			// Check file size
			clearstatcache();
			$sizematch = true;
			$filesize = @filesize($filePath);

			if (($filesize !== false) && ($filesize != $size))
			{
				$output->warning(sprintf("Filesize mismatch on %s", $filePath));

				$sizematch = false;
			}

			if ($sizematch)
			{
				$filename = $params['filename'];

				// Try renaming
				if (strlen($filename))
				{
					@rename($filePath, $path . DIRECTORY_SEPARATOR . $filename);

					if (file_exists($path . DIRECTORY_SEPARATOR . $filename))
					{
						$output->info(sprintf("Successfully renamed %s to %s", $name, $filename));
					}
					else
					{
						$output->info(sprintf("Failed to rename %s to %s", $name, $filename));
					}
				}

				$output->info(sprintf("Successfully downloaded %s", $name), true);
			}
		}

	}

	/**
	 * Delete the backup archives of a backup record. Useful to delete the backup archive files from the server after downloading
	 * them to your internal network.
	 *
	 * @param   int      $id       The backup record ID.
	 * @param   Output   $output   The output handler object
	 * @param   Options  $options  API options
	 */
	public function deleteFiles($id, Output $output, Options $options)
	{
		$api     = new Api($options, $output);

		if ($id <= 0)
		{
			throw new NoBackupID();
		}

		$data = $api->doQuery('deleteFiles', [
			'backup_id' => $id
		]);

		if ($data->body->status != 200)
		{
			throw new CannotDeleteFiles($id, $data->body->status, $data->body->data);
		}

		$output->header("Files of backup record $id were successfully deleted");

	}

	/**
	 * @param array   $params
	 * @param Output  $output
	 * @param Options $options
	 *
	 * @return array
	 */
	private function getBackupArchiveInformation(array $params, Output $output, Options $options)
	{
		$api             = new Api($options, $output);
		$data            = $api->doQuery('getBackupInfo', array('backup_id' => $params['id']));
		$parts           = $data->body->data->multipart;
		$fileDefinitions = $data->body->data->filenames;
		$fileRecords     = array();

		foreach ($fileDefinitions as $fileDefinition)
		{
			$fileRecords[$fileDefinition->part] = (object) [
				'name' => $fileDefinition->name,
				'size' => $fileDefinition->size,
			];
		}

		if ($parts <= 0)
		{
			$parts = 1;
		}

		if (!count($fileDefinitions))
		{
			throw new NoFilesInBackupRecord($params['id']);
		}

		return [
			$data,
			$parts,
			$fileRecords,
		];
	}
}
