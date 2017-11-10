<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Command;


use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Model\Backup as BackupModel;
use Akeeba\RemoteCLI\Model\Download as DownloadModel;
use Akeeba\RemoteCLI\Model\Test as TestModel;
use Akeeba\RemoteCLI\Output\Output;

class Download extends AbstractCommand
{
	public function execute(Cli $input, Output $output)
	{
		$this->assertConfigured($input);

		$testModel     = new TestModel();
		$downloadModel = new DownloadModel();

		$downloadParameters = $downloadModel->getValidatedParameters($input);

		// Find the best options to connect to the API
		$options = $this->getApiOptions($input);
		$options = $testModel->getBestOptions($input, $output, $options);

		// Now download the backup archive
		$downloadModel->download($downloadParameters, $output, $options);

		// Do I also have to delete the files after I download them?
		if ($downloadParameters['delete'])
		{
			$downloadModel->deleteFiles($downloadParameters, $output, $options);
		}
	}

}
