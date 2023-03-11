<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Command;


use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Output\Output;
use Akeeba\RemoteCLI\Api\Exception\NoBackupID;
use Akeeba\RemoteCLI\Api\HighLevel\Backup as BackupModel;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;

class BackupInfo extends AbstractCommand
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
		$backup = $model->getBackup($input, $output, $options);

		$output->header("Statistic info for backup #".$input->getInt('id'));

		if (empty($backup))
		{
			$output->warning('No backup records was found');

			return;
		}

		$status = ($backup->status == 'complete') && !($backup->filesexist) ? 'obsolete' : $backup->status;
		$status = str_pad($status, 8);

		// If multipart is 0 it means that's a single backup archive
		$parts	= (!$backup->multipart ? 1 : $backup->multipart);

		$line   = sprintf('%6u|%s|%s|%s|%s|%s|%s',
							$backup->id,
							$backup->backupstart,
							$status,
							$backup->description,
							$backup->profile_id,
							$parts,
							$backup->size ?? ''
		);

		$output->info($line, true);
	}

	/**
	 * Make sure that the user has provided enough and correct configuration for this command to run.
	 *
	 * We are overriding this to run additional checks which make sense in the context of this command.
	 *
	 * @param   Cli  $input  The input object.
	 *
	 * @return  void
	 */
	protected function assertConfigured(Cli $input): void
	{
		parent::assertConfigured($input);

		$id = $input->getInt('id', -1);

		if ($id <= 0)
		{
			throw new NoBackupID();
		}
	}
}
