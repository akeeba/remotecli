<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Command;


use Akeeba\RemoteCLI\Api\HighLevel\Backup as BackupModel;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;
use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Output\Output;

class Listbackups extends AbstractCommand
{
	public function execute(): void
	{
		$this->assertConfigured();

		// Get and print the backup records
		$from    = $this->input->getInt('from', 0);
		$limit   = $this->input->getInt('limit', 200);
		$backups = $this->getApiObject()->getBackups($from, $limit);

		$this->output->header("List of backup records");

		if (empty($backups))
		{
			$this->logger->warning('No backup records were found');

			return;
		}

		foreach ($backups as $record)
		{
			$status = ($record->status == 'complete') && !($record->filesexist) ? 'obsolete' : $record->status;
			$status = str_pad($status, 8);
			$meta   = str_pad($record->meta, 8);

			// If multipart is 0 it means that's a single backup archive
			$parts = (!$record->multipart ? 1 : $record->multipart);

			$line = sprintf('%6u|%s|%s|%s|%s|%s|%s|%s',
				$record->id,
				$record->backupstart,
				$status,
				$record->description,
				$record->profile_id,
				$parts,
				$meta,
				$record->size ?? ''
			);

			$this->logger->debug($line);
			$this->output->info($line, true);
		}
	}
}
