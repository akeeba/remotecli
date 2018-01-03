<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright Copyright (c)2008-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Command;


use Akeeba\RemoteCLI\Exception\NoBackupID;
use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Model\Download as DownloadModel;
use Akeeba\RemoteCLI\Model\Test as TestModel;
use Akeeba\RemoteCLI\Output\Output;

class Delete extends AbstractCommand
{
	public function execute(Cli $input, Output $output)
	{
		$this->assertConfigured($input);

		$testModel     = new TestModel();
		$downloadModel = new DownloadModel();

		$id = $input->getInt('id');

		// Find the best options to connect to the API
		$options = $this->getApiOptions($input);
		$options = $testModel->getBestOptions($input, $output, $options);

		// Now delete the files of that backup record
		$downloadModel->deleteFiles($id, $output, $options);
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
	protected function assertConfigured(Cli $input)
	{
		parent::assertConfigured($input);

		$id = $input->getInt('id', -1);

		if (!$id <= 0)
		{
			throw new NoBackupID();
		}
	}
}
