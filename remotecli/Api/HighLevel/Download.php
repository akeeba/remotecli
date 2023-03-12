<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\HighLevel;

use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\DataShape\DownloadOptions;
use Akeeba\RemoteCLI\Api\Exception\CannotDownloadFile;
use Akeeba\RemoteCLI\Api\Exception\CannotWriteFile;
use Akeeba\RemoteCLI\Api\Exception\CommunicationError;
use Akeeba\RemoteCLI\Api\Exception\NoBackupID;
use Akeeba\RemoteCLI\Api\Exception\NoFilesInBackupRecord;
use Akeeba\RemoteCLI\Api\Exception\NoSuchBackupRecord;
use Akeeba\RemoteCLI\Api\Exception\NoSuchPart;
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

	private function downloadIntoFile(string $url, $fp, int $from = 0, int $to = 0): void
	{
		if ($to < $from)
		{
			[$to, $from] = [$from, $to];
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSLVERSION, 0);
		curl_setopt($ch, CURLOPT_CAINFO, $this->connector->getOptions()->capath);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FILE, $fp);

		if (!empty($from) || !empty($to))
		{
			curl_setopt($ch, CURLOPT_RANGE, sprintf('%d-%d', $from, $to));
		}

		$result      = curl_exec($ch);
		$errno       = curl_errno($ch);
		$errmsg      = curl_error($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($result === false)
		{
			throw new CommunicationError(
				$errno,
				sprintf('PHP cURL library error #%d with message ‘%s’', $errno, $errmsg)
			);
		}

		if ($http_status > 299)
		{
			throw new CommunicationError(
				$http_status,
				sprintf('Unexpected HTTP status %d', $http_status)
			);
		}
	}

	private function downloadHTTP(DownloadOptions $params): void
	{
		// Get the backup info
		[, $parts, $fileInformation] = $this->getBackupArchiveInformation($params);

		$path       = $params->path;
		$part_start = 1;
		$part_end   = $parts;

		// Was I asked to download only one specific part?
		if ($params->part > 0)
		{
			$part_start = $params->part;
			$part_end   = $params->part;
		}

		for ($part = $part_start; $part <= $part_end; $part++)
		{
			// Open file pointer
			$name = $fileInformation[$part]?->name;
			$size = $fileInformation[$part]?->size;

			if (empty($name))
			{
				throw new NoSuchPart();
			}

			$filePath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($filePath, 'w');

			if ($fp === false)
			{
				throw new CannotWriteFile($filePath);
			}

			try
			{
				// Get the signed URL
				$url = $this->connector->makeURL('downloadDirect', [
					'backup_id' => $params->id,
					'part_id'   => $part,
				], true);

				$this->downloadIntoFile($url, $fp, 0, 0);
			}
			catch (CommunicationError $e)
			{
				throw new CannotDownloadFile(
					sprintf(
						'Could not download file ‘%s’ -- Network error “%s”',
						$filePath,
						$e->getMessage()
					),
					105,
					$e
				);
			}
			catch (\Throwable $e)
			{
				throw new CannotDownloadFile(
					sprintf(
						'Could not download file ‘%s’ -- Uncaught error “%s”',
						$filePath,
						$e->getMessage()
					),
					105,
					$e
				);
			}
			finally
			{
				fclose($fp);
			}

			// Check file size
			clearstatcache();
			$filesize = @filesize($filePath);

			if ($filesize !== false && $filesize != $size)
			{
				$this->logger->warning(
					sprintf(
						'Filesize mismatch on %s -- Expected %d, got %d',
						$filePath,
						$filesize,
						$size
					)
				);

				throw new CannotDownloadFile(
					sprintf(
						'Could not download file ‘%s’ -- Expected file size %d, got %d',
						$filePath,
						$filesize,
						$size
					),
					105
				);
			}

			$filename = $params->filename;

			// Try renaming
			if (strlen($filename))
			{
				@rename($filePath, $path . DIRECTORY_SEPARATOR . $filename);

				if (file_exists($path . DIRECTORY_SEPARATOR . $filename))
				{
					$this->logger->debug(sprintf("Successfully renamed %s to %s", $name, $filename));
				}
				else
				{
					$this->logger->debug(sprintf("Failed to rename %s to %s", $name, $filename));
				}
			}

			$this->logger->debug(sprintf("Successfully downloaded %s", $name));
		}
	}

	private function downloadChunk(DownloadOptions $params): void
	{
		// Get the backup info
		[, $parts, $fileInformation] = $this->getBackupArchiveInformation($params);

		$path       = $params->path;
		$chunk_size = $params->chunkSize;
		$part_start = 1;
		$part_end   = $parts;

		// Was I asked to download only one specific part?
		if ($params->part > 0)
		{
			$part_start = $params->part;
			$part_end   = $params->part;
		}

		for ($part = $part_start; $part <= $part_end; $part++)
		{
			// Open file pointer
			$name     = $fileInformation[$part]->name;
			$size     = $fileInformation[$part]->size;
			$filePath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($filePath, 'w');

			if ($fp === false)
			{
				throw new CannotWriteFile($filePath);
			}

			$frag = 0;
			$done = false;

			while (!$done)
			{
				$data = $this->connector->doQuery('download', [
					'backup_id'  => $params->id,
					'part'       => $part,
					'segment'    => ++$frag,
					'chunk_size' => $chunk_size,
				]);

				switch ($data->body->status)
				{
					case 200:
						$rawData = base64_decode($data->body->data);
						$len     = strlen($rawData); //echo "\tWriting $len bytes\n";
						$this->logger->debug(sprintf('Writing a chunk of %d bytes', $len));
						fwrite($fp, $rawData);
						unset($rawData);
						unset($data);
						break;

					case 404:
						if ($frag === 1)
						{
							throw new NoFilesInBackupRecord($params->id);
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
			$filesize = @filesize($filePath);

			if ($filesize !== false && $filesize != $size)
			{
				$this->logger->warning(
					sprintf(
						'Filesize mismatch on %s -- Expected %d, got %d',
						$filePath,
						$filesize,
						$size
					)
				);

				throw new CannotDownloadFile(
					sprintf(
						'Could not download file ‘%s’ -- Expected file size %d, got %d',
						$filePath,
						$filesize,
						$size
					),
					105
				);
			}

			$filename = $params->filename;

			// Try renaming
			if (strlen($filename))
			{
				@rename($filePath, $path . DIRECTORY_SEPARATOR . $filename);

				if (file_exists($path . DIRECTORY_SEPARATOR . $filename))
				{
					$this->logger->debug(sprintf("Successfully renamed %s to %s", $name, $filename));
				}
				else
				{
					$this->logger->debug(sprintf("Failed to rename %s to %s", $name, $filename));
				}
			}

			$this->logger->debug(sprintf("Successfully downloaded %s", $name));
		}

	}

	private function downloadCURL(DownloadOptions $params): void
	{
		// Get the backup info
		[, $parts, $fileInformation] = $this->getBackupArchiveInformation($params);

		$path           = $params->path;
		$url            = $params->url;
		$authentication = $params->authentication;
		$part_start     = 1;
		$part_end       = $parts;

		// Was I asked to download only one specific part?
		if ($params->part > 0)
		{
			$part_start = $params->part;
			$part_end   = $params->part;
		}

		for ($part = $part_start; $part <= $part_end; $part++)
		{
			// Open file pointer
			$name     = $fileInformation[$part]->name;
			$size     = $fileInformation[$part]->size;
			$filePath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($filePath, 'w');

			if ($fp === false)
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
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0.1) Gecko/20110506 Firefox/4.0.1');
			curl_setopt($ch, CURLOPT_CAINFO, $this->connector->getOptions()->capath);

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
				throw new CannotDownloadFile(
					sprintf(
						'Could not download ‘%s’ over cURL -- cURL error #%d : %s',
						$filePath,
						$errno,
						$errmessage
					)
				);
			}

			if ($status === false)
			{
				throw new NoFilesInBackupRecord($params->id);
			}

			// Check file size
			clearstatcache();
			$filesize  = @filesize($filePath);

			if ($filesize !== false && $filesize != $size)
			{
				$this->logger->warning(
					sprintf(
						'Filesize mismatch on %s -- Expected %d, got %d',
						$filePath,
						$filesize,
						$size
					)
				);

				throw new CannotDownloadFile(
					sprintf(
						'Could not download file ‘%s’ -- Expected file size %d, got %d',
						$filePath,
						$filesize,
						$size
					),
					105
				);
			}

			$filename = $params->filename;

			// Try renaming
			if (strlen($filename))
			{
				@rename($filePath, $path . DIRECTORY_SEPARATOR . $filename);

				if (file_exists($path . DIRECTORY_SEPARATOR . $filename))
				{
					$this->logger->debug(sprintf("Successfully renamed %s to %s", $name, $filename));
				}
				else
				{
					$this->logger->debug(sprintf("Failed to rename %s to %s", $name, $filename));
				}
			}

			$this->logger->debug(sprintf("Successfully downloaded %s", $name));
		}
	}

	private function getBackupArchiveInformation(DownloadOptions $params): array
	{
		$data            = $this->connector->doQuery(
			'getBackupInfo', [
				'backup_id' => $params->id,
			]
		);

		if ($data->body->status == 404)
		{
			throw new NoSuchBackupRecord();
		}

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
			throw new NoFilesInBackupRecord($params->id);
		}

		return [
			$data,
			$parts,
			$fileRecords,
		];
	}
}
