<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Api\HighLevel;


use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\DataShape\BackupOptions;
use Akeeba\RemoteCLI\Api\Exception\RemoteError;
use Joomla\Data\DataObject;
use Psr\Log\LoggerInterface;

class Backup
{
	private LoggerInterface $logger;

	public function __construct(private Connector $connector)
	{
		$this->logger = $this->connector->getOptions()->logger;
	}

	public function __invoke(BackupOptions $backupOptions, ?callable $progressCallback = null): object
	{
		$info           = $this->startBackup($backupOptions);
		$data           = $info['data'];
		$backupID       = $info['backupID'] ?? null;
		$backupRecordID = $info['backupRecordID'] ?? 0;
		$archive        = $info['archive'] ?? '';

		while ($data?->body?->data?->HasRun)
		{
			if ($progressCallback)
			{
				$progressCallback($data);
			}

			$backupID       = ($info['backupID'] ?? null) ?: $backupID;
			$backupRecordID = ($info['backupRecordID'] ?? 0) ?: $backupRecordID;
			$archive        = ($info['archive'] ?? '') ?: $archive;
			$info           = $this->stepBackup($backupID);
			$data           = $info['data'];
		}

		if ($data->body->status != 200)
		{
			throw new RemoteError('Error ' . $data->body->status . ": " . $data->body->data);
		}

		return new DataObject([
			'id'      => $backupRecordID,
			'archive' => $archive,
		]);
	}

	private function handleAPIResponse(object $data): array
	{
		$backupID       = null;
		$backupRecordID = 0;
		$archive        = '';

		if ($data->body?->status != 200)
		{
			throw new RemoteError('Error ' . $data->body->status . ": " . $data->body->data);
		}

		if (isset($data->body->data->BackupID))
		{
			$backupRecordID = $data->body->data->BackupID;
			$this->logger->debug('Got backup record ID: ' . $backupRecordID);
		}

		if (isset($data->body->data->backupid))
		{
			$backupID = $data->body->data->backupid;
			$this->logger->debug('Got backupID: ' . $backupID);
		}

		if (isset($data->body->data->Archive))
		{
			$archive = $data->body->data->Archive;
			$this->logger->debug('Got archive name: ' . $archive);
		}

		$info = [
			'backupID'       => $backupID,
			'backupRecordID' => $backupRecordID,
			'archive'        => $archive,
		];

		return $info;
	}

	private function startBackup(BackupOptions $backupOptions): array
	{
		$data = $this->connector->doQuery('startBackup', [
			'profile'     => (int) $backupOptions->profile,
			'description' => $backupOptions->description ?: 'Remote backup',
			'comment'     => $backupOptions->comment,
		]);
		$info = $this->handleAPIResponse($data);

		$info['data'] = $data;

		return $info;
	}

	private function stepBackup(?int $backupID): array
	{
		$params = [];

		if ($backupID)
		{
			$params['backupid'] = $backupID;
		}

		$data = $this->connector->doQuery('stepBackup', $params);
		$info = $this->handleAPIResponse($data);

		$info['data'] = $data;

		return $info;
	}
}
