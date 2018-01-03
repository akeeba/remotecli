<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright Copyright (c)2008-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Command;


use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Model\Profiles as ProfilesModel;
use Akeeba\RemoteCLI\Model\Test as TestModel;
use Akeeba\RemoteCLI\Output\Output;

class Profiles extends AbstractCommand
{
	public function execute(Cli $input, Output $output)
	{
		$this->assertConfigured($input);

		$testModel = new TestModel();
		$model     = new ProfilesModel();

		// Find the best options to connect to the API
		$options = $this->getApiOptions($input);
		$options = $testModel->getBestOptions($input, $output, $options);

		// Get and print the backup profiles
		$profiles = $model->getProfiles($input, $output, $options);

		$output->header("List of profiles");

		if (empty($profiles))
		{
			$output->warning('No backup profiles were found');

			return;
		}

		foreach ($profiles as $profile)
		{
			$line = sprintf('%4u|%s', $profile->id, $profile->name);

			$output->info($line, true);
		}
	}
}
