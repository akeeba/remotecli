<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\OLD\RemoteCLI\Command;


use Akeeba\OLD\RemoteCLI\Exception\NoProfileID;
use Akeeba\OLD\RemoteCLI\Input\Cli;
use Akeeba\OLD\RemoteCLI\Model\Profiles as ProfilesModel;
use Akeeba\OLD\RemoteCLI\Output\Output;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;

class ProfileExport extends AbstractCommand
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
		$profile_data = $model->exportConfiguration($input, $output, $options);

		// base64 encode configuration and filters since there could some bad chars breaking the format
		$configuration = base64_encode($profile_data->configuration);
		$filters	   = base64_encode($profile_data->filters);

		$output->header(sprintf("Export data for profile %d", $input->getInt('id')));
		$output->info(sprintf('%s|%s|%s', $profile_data->description, $configuration, $filters), true);
	}

	protected function assertConfigured(Cli $input): void
	{
		parent::assertConfigured($input);

		$id = $input->getInt('id', -1);

		if ($id <= 0)
		{
			throw new NoProfileID();
		}
	}
}
