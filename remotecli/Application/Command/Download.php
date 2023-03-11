<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Command;


use Akeeba\RemoteCLI\Api\HighLevel\Download as DownloadModel;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;
use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Output\Output;

class Download extends AbstractCommand
{
	public function prepare(): void
	{
		if ($this->input->getBool('D', false))
		{
			$this->input->set('delete', true);
		}
	}

	public function execute(): void
	{
		$this->assertConfigured();

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
			$downloadModel->deleteFiles($downloadParameters['id'], $output, $options);
		}
	}

}
