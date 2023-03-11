<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Command;


use Akeeba\RemoteCLI\Api\DataShape\BackupOptions;
use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Output\Output;

class Backup extends AbstractCommand
{
	public function execute(Cli $input, Output $output): void
	{
		$this->assertConfigured($input);

		$mustDownload  = $input->getBool('download', false);

		/**
		 * DO NOT DELETE!
		 *
		 * If I need to download after backup I must do a preliminary validation of the download parameters. Otherwise
		 * I might waste my time backing up before I realize that I cannot download the backup because some important
		 * parameter was missing all along.
		 */
		if ($mustDownload)
		{
			$this->getDownloadOptions($input);
		}

		// Find the best options to connect to the API
		$api = $this->getApiObject($input, $output);

		// Take a backup
		$backupOptions = new BackupOptions(			[
			'profile'     => $input->getInt('profile', 1),
			'description' => $input->get('description', 'Remote backup', 'raw'),
			'comment'     => $input->get('comment', '', 'raw'),
		]);

		[$backupRecordID, $archive] = $api->backup($backupOptions);

		// Do I also need to download the backup archive?
		if (!$mustDownload)
		{
			return;
		}

		$input->set('id', $backupRecordID);
		$input->set('archive', $archive);
		$input->set('part', -1);

		/**
		 * DO NOT DELETE!
		 *
		 * I have to get the download parameters *again* since I've updated $input with the backup information. However,
		 * I can not delete neither this instance of getValidatedParameters nor the one above because BOTH are required
		 * for different reasons: the former to validate the download configuration before taking a backup; the latter
		 * to get the actual parameters which let me download the backup archive.
		 */
		$downloadParameters = $this->getDownloadOptions($input);
		$api->download($downloadParameters);

		// Do I also have to delete the files after I download them?
		if ($downloadParameters['delete'])
		{
			$api->deleteFiles($downloadParameters['id']);
		}
	}

	public function prepare(Cli $input): void
	{
		if ($input->getBool('d', false))
		{
			$input->set('download', true);
		}

		if ($input->getBool('D', false))
		{
			$input->set('delete', true);
		}
	}
}
