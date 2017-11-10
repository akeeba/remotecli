<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Command;


use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Model\Backup as BackupModel;
use Akeeba\RemoteCLI\Model\Download;
use Akeeba\RemoteCLI\Model\Test as TestModel;
use Akeeba\RemoteCLI\Output\Output;

class Backup extends AbstractCommand
{
	public function execute(Cli $input, Output $output)
	{
		$this->assertConfigured($input);

		$testModel   = new TestModel();
		$backupModel = new BackupModel();
		$downloadModel      = new Download();
		$mustDownload = $input->getBool('download', false);

		/**
		 * DO NOT DELETE!
		 *
		 * If I need to download after backup I must do a preliminary validation of the download parameters. Otherwise
		 * I might waste my time backing up before I realize that I cannot download the backup because some important
		 * parameter was missing all along.
		 */
		if ($mustDownload)
		{
			$downloadParameters = $downloadModel->getValidatedParameters($input);
		}

		// Find the best options to connect to the API
		$options = $this->getApiOptions($input);
		$options = $testModel->getBestOptions($input, $output, $options);

		// Take a backup
		list($backupRecordID, $archive) = $backupModel->backup($input, $output, $options);

		// Do I also need to download the backup archive?
		if (!$mustDownload)
		{
			return;
		}

		$input->set('id', $backupRecordID);
		$input->set('archive', $archive);

		/**
		 * DO NOT DELETE!
		 *
		 * I have to get the download parameters *again* since I've updated $input with the backup information. However,
		 * I can not delete neither this instance of getValidatedParameters nor the one above because BOTH are required
		 * for different reasons: the former to validate the download configuration before taking a backup; the latter
		 * to get the actual parameters which let me download the backup archive.
		 */
		$downloadParameters = $downloadModel->getValidatedParameters($input);
		$downloadModel->download($downloadParameters, $output, $options);

		// Do I also have to delete the files after I download them?
		if ($downloadParameters['delete'])
		{
			$downloadModel->deleteFiles($downloadParameters, $output, $options);
		}
	}

}
