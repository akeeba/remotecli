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
use Akeeba\RemoteCLI\Api\HighLevel\Download as DownloadModel;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;

class Delete extends AbstractCommand
{
	public function execute(Cli $input, Output $output): void
	{
		$this->assertConfigured($input);

		$testModel     = new TestModel();
		$downloadModel = new DownloadModel();

		$id = $input->getInt('id');

		// Find the best options to connect to the API
		$options = $this->getApiOptions($input);
		$options = $testModel->getBestOptions($input, $output, $options);

		// Now delete the files of that backup record
		$downloadModel->delete($id, $output, $options);
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
