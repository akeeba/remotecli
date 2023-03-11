<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Command;


use Akeeba\RemoteCLI\Api\Exception\NoProfileID;
use Akeeba\RemoteCLI\Api\HighLevel\GetProfiles as ProfilesModel;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;
use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Output\Output;

class ProfileExport extends AbstractCommand
{
	public function execute(): void
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

	protected function assertConfigured(): void
	{
		parent::assertConfigured();

		$id = $this->input->getInt('id', -1);

		if ($id <= 0)
		{
			throw new NoProfileID();
		}
	}
}
