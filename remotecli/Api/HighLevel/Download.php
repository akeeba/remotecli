<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\HighLevel;

use Akeeba\OLD\RemoteCLI\Output\Output;
use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\DataShape\DownloadOptions;
use Akeeba\RemoteCLI\Api\Exception\CannotDownloadFile;
use Akeeba\RemoteCLI\Api\Exception\CannotWriteFile;
use Akeeba\RemoteCLI\Api\Exception\NoBackupID;
use Akeeba\RemoteCLI\Api\Exception\NoFilesInBackupRecord;
use Akeeba\RemoteCLI\Api\Options;
use Exception;
use Psr\Log\LoggerInterface;

class Download
{
	private LoggerInterface $logger;

	public function __construct(private Connector $connector)
	{
		$this->logger = $this->connector->getOptions()->logger;
	}

	public function __invoke(DownloadOptions $options): void
	{
		if ($options->id <= 0)
		{
			throw new NoBackupID();
		}

		switch ($options->mode)
		{
			case 'http':
				$this->downloadHTTP($options);
				break;

			case 'chunk':
				$this->downloadChunk($options);
				break;

			case 'curl':
				$this->downloadCURL($options);
				break;
		}
	}

	private function downloadHTTP(DownloadOptions $params): void
	{
		// Get the backup info
		[, $parts, $fileInformation] = $this->getBackupArchiveInformation($params);

		$path       = $params['path'];
		$part_start = 1;
		$part_end   = $parts;

		// Was I asked to download only one specific part?
		if ($params['part'] > 0)
		{
			$part_start = $params['part'];
			$part_end   = $params['part'];
		}

		for ($part = $part_start; $part <= $part_end; $part++)
		{
			// Open file pointer
			$name     = $fileInformation[$part]?->name;
			$size     = $fileInformation[$part]?->size;
			$filePath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($filePath, 'w');

			if ($fp == false)
			{
				throw new CannotWriteFile($filePath);
			}

			try
			{
				// Get the signed URL
				$url = $api->makeURL('downloadDirect', [
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
								'cafile' => $options->capath,
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
			$filesize  = @filesize($filePath);

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
	 * Download a backup archive using multiple chunk HTTP download through the JSON API. Recommended for larger
	 * archives.
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
		[, $parts, $fileInformation] = $this->getBackupArchiveInformation($params, $output, $options);

		$api        = new Connector($options, $output);
		$path       = $params['path'];
		$chunk_size = $params['chunkSize'];

		$part_start = 1;
		$part_end   = $parts;

		// Did I asked to download only one specific part?
		if ($params['part'] > 0)
		{
			$part_start = $params['part'];
			$part_end   = $params['part'];
		}

		for ($part = $part_start; $part <= $part_end; $part++)
		{
			// Open file pointer
			$name     = $fileInformation[$part]->name;
			$size     = $fileInformation[$part]->size;
			$filePath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($filePath, 'w');

			if ($fp == false)
			{
				throw new CannotWriteFile($filePath);
			}

			$frag = 0;
			$done = false;

			while (!$done)
			{
				$data = $api->doQuery('download', [
					'backup_id'  => $params['id'],
					'part'       => $part,
					'segment'    => ++$frag,
					'chunk_size' => $chunk_size,
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
			$filesize  = @filesize($filePath);

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
	 * Download a backup archive using cURL, bypassing the JSON API. This is useful when you have (S)FTP or WebDAV
	 * access to the location of your backup archives.
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
		[, $parts, $fileInformation] = $this->getBackupArchiveInformation($params, $output, $options);

		$path           = $params['path'];
		$url            = $params['url'];
		$authentication = $params['authentication'];

		$part_start = 1;
		$part_end   = $parts;

		// Did I asked to download only one specific part?
		if ($params['part'] > 0)
		{
			$part_start = $params['part'];
			$part_end   = $params['part'];
		}

		for ($part = $part_start; $part <= $part_end; $part++)
		{
			// Open file pointer
			$name     = $fileInformation[$part]->name;
			$size     = $fileInformation[$part]->size;
			$filePath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($filePath, 'w');

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

			if (!empty($authentication))
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
			$filesize  = @filesize($filePath);

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

	private function getBackupArchiveInformation(DownloadOptions $params): array
	{
		$data            = $this->connector->doQuery(
			'getBackupInfo', [
				'backup_id' => $params['id'],
			]
		);
		$parts           = $data->body->data->multipart;
		$fileDefinitions = $data->body->data->filenames;
		$fileRecords     = [];

		foreach ($fileDefinitions as $fileDefinition)
		{
			$fileRecords[$fileDefinition->part] = (object) [
				'name' => $fileDefinition->name,
				'size' => $fileDefinition->size,
			];
		}

		$parts = max($parts, 1);

		if (!(is_array($fileDefinitions) || $fileDefinitions instanceof \Countable ? count($fileDefinitions) : 0))
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
