<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

use Akeeba\BackupJsonApi\Exception\NoBackupID;
use Akeeba\RemoteCLI\Application\Input\Cli;

class BackupInfo extends AbstractCommand
{
	public function execute(): void
	{
		$this->assertConfigured();

		// Get and print the backup records
		$id = $this->input->getInt('id');
		$backup = $this->getApiObject()->getBackup($id);

		$this->output->header("Statistic info for backup #" . $id);

		if (empty($backup))
		{
			$this->logger->warning('No backup records was found');

			return;
		}

		$status = ($backup->status == 'complete') && !($backup->filesexist) ? 'obsolete' : $backup->status;

		if ($status === 'obsolete' && !empty($backup->remote_filename))
		{
			$status = 'remote';
		}

		// If multipart is 0 it means that's a single backup archive
		$parts = (!$backup->multipart ? 1 : $backup->multipart);

		$line = sprintf('%6u|%s|%-8s|%s|%s|%s|%s',
			$backup->id,
			$backup->backupstart,
			$status,
			$backup->description,
			$backup->profile_id,
			$parts,
			$backup->size ?? ''
		);

		$this->logger->debug($line);
		$this->output->info($line, true);
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
	protected function assertConfigured(): void
	{
		parent::assertConfigured();

		$id = $this->input->getInt('id', -1);

		if ($id <= 0)
		{
			throw new NoBackupID();
		}
	}
}
