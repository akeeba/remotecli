<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\OLD\RemoteCLI\Command;


use Akeeba\OLD\RemoteCLI\Input\Cli;
use Akeeba\OLD\RemoteCLI\Output\Output;
use Akeeba\RemoteCLI\Api\Exception\NoProfileData;
use Akeeba\RemoteCLI\Api\HighLevel\GetProfiles as ProfilesModel;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;

class ProfileImport extends AbstractCommand
{
	public function execute(Cli $input, Output $output): void
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

	protected function assertConfigured(Cli $input): void
	{
		parent::assertConfigured($input);

		$data = $input->get('data', '', 'raw');

		if (!$data)
		{
			throw new NoProfileData();
		}
	}
}
