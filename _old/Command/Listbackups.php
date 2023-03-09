<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\OLD\RemoteCLI\Command;


use Akeeba\OLD\RemoteCLI\Input\Cli;
use Akeeba\OLD\RemoteCLI\Output\Output;
use Akeeba\RemoteCLI\Api\HighLevel\Backup as BackupModel;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;

class Listbackups extends AbstractCommand
{
	public function execute(Cli $input, Output $output): void
	{
		$this->assertConfigured($input);

		$testModel = new TestModel();
		$model     = new BackupModel();

		// Find the best options to connect to the API
		$options = $this->getApiOptions($input);
		$options = $testModel->getBestOptions($input, $output, $options);

		// Get and print the backup records
		$backups = $model->getBackups($input, $output, $options);

		$output->header("List of backup records");

		if (empty($backups))
		{
			$output->warning('No backup records were found');

			return;
		}

		foreach ($backups as $record)
		{
			$status = ($record->status == 'complete') && !($record->filesexist) ? 'obsolete' : $record->status;
			$status = str_pad($status, 8);
			$meta	= str_pad($record->meta, 8);

			// If multipart is 0 it means that's a single backup archive
			$parts	= (!$record->multipart ? 1 : $record->multipart);

			$line   = sprintf('%6u|%s|%s|%s|%s|%s|%s|%s',
								$record->id,
								$record->backupstart,
								$status,
								$record->description,
								$record->profile_id,
								$parts,
								$meta,
								$record->size ?? ''
			);

			$output->info($line, true);
		}
	}
}
