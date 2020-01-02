<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Command;


use Akeeba\RemoteCLI\Exception\NoProfileData;
use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Model\Profiles as ProfilesModel;
use Akeeba\RemoteCLI\Model\Test as TestModel;
use Akeeba\RemoteCLI\Output\Output;

class ProfileImport extends AbstractCommand
{
	public function execute(Cli $input, Output $output)
	{
		$this->assertConfigured($input);

		$testModel = new TestModel();
		$model     = new ProfilesModel();

		// Find the best options to connect to the API
		$options = $this->getApiOptions($input);
		$options = $testModel->getBestOptions($input, $output, $options);

		// Get and print profile configuration
		$model->importConfiguration($input, $output, $options);

		$output->info(sprintf("Profile imported"));
	}

	protected function assertConfigured(Cli $input)
	{
		parent::assertConfigured($input);

		$data = $input->get('data', '', 'raw');

		if (!$data)
		{
			throw new NoProfileData();
		}
	}
}
