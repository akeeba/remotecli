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
	public function execute(): void
	{
		$this->assertConfigured();

		$mustDownload  = $this->input->getBool('download', false);

		/**
		 * DO NOT DELETE!
		 *
		 * If I need to download after backup I must do a preliminary validation of the download parameters. Otherwise
		 * I might waste my time backing up before I realize that I cannot download the backup because some important
		 * parameter was missing all along.
		 */
		if ($mustDownload)
		{
			$this->getDownloadOptions();
		}

		// Find the best options to connect to the API
		$api = $this->getApiObject();

		// Take a backup
		$backupOptions = new BackupOptions(			[
			'profile'     => $this->input->getInt('profile', 1),
			'description' => $this->input->get('description', 'Remote backup', 'raw'),
			'comment'     => $this->input->get('comment', '', 'raw'),
		]);

		[$backupRecordID, $archive] = $api->backup($backupOptions);

		// Do I also need to download the backup archive?
		if (!$mustDownload)
		{
			return;
		}

		$this->input->set('id', $backupRecordID);
		$this->input->set('archive', $archive);
		$this->input->set('part', -1);

		/**
		 * DO NOT DELETE!
		 *
		 * I have to get the download parameters *again* since I've updated $input with the backup information. However,
		 * I can not delete neither this instance of getValidatedParameters nor the one above because BOTH are required
		 * for different reasons: the former to validate the download configuration before taking a backup; the latter
		 * to get the actual parameters which let me download the backup archive.
		 */
		$downloadParameters = $this->getDownloadOptions();
		$api->download($downloadParameters);

		// Do I also have to delete the files after I download them?
		if ($downloadParameters['delete'])
		{
			$api->deleteFiles($downloadParameters['id']);
		}
	}

	public function prepare(): void
	{
		if ($this->input->getBool('d', false))
		{
			$this->input->set('download', true);
		}

		if ($this->input->getBool('D', false))
		{
			$this->input->set('delete', true);
		}
	}
}
