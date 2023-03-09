<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\OLD\RemoteCLI\Command;


use Akeeba\OLD\RemoteCLI\Exception\LiveUpdateStability;
use Akeeba\OLD\RemoteCLI\Input\Cli;
use Akeeba\OLD\RemoteCLI\Model\Update as UpdateModel;
use Akeeba\OLD\RemoteCLI\Output\Output;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;

class Update extends AbstractCommand
{
	public function execute(Cli $input, Output $output): void
	{
		$this->assertConfigured($input);

		$testModel   = new TestModel();

		// Find the best options to connect to the API
		$options = $this->getApiOptions($input);
		$options = $testModel->getBestOptions($input, $output, $options);

		$updateModel = new UpdateModel();
		$updateInfo = $updateModel->getUpdateInformation($input, $output, $options);

		$output->header('Update found');
		$output->info("Version   : {$updateInfo->version}");
		$output->info("Date      : {$updateInfo->date}");
		$output->info("Stability : {$updateInfo->stability}");

		// Is it stable enough?
		$minStability = $input->getCmd('minimum-stability', 'alpha');
		$minStability = !in_array($minStability, ['alpha', 'beta', 'rc', 'stable']) ? 'alpha' : $minStability;
		$stabilities = array('alpha' => 0, 'beta' => 30, 'rc' => 60, 'stable' => 100);
		$min         = array_key_exists($minStability, $stabilities) ? $stabilities[$minStability] : 0;
		$cur         = array_key_exists($updateInfo->stability, $stabilities) ? $stabilities[$updateInfo->stability] : 0;

		if ($cur < $min)
		{
			throw new LiveUpdateStability("The available version does not fulfil your minimum stability preferences");
		}

		$output->header('Downloading update');
		$updateModel->downloadUpdate($input, $output, $options);
		$output->info('Update downloaded to your server', true);

		$output->header('Extracting update');
		$updateModel->extractUpdate($input, $output, $options);
		$output->info('Update extracted to your server', true);

		$output->header('Installing update');
		$updateModel->installUpdate($input, $output, $options);
		$output->info('updateInstall', true);

		$output->header('Cleaning up');
		$updateModel->cleanupUpdate($input, $output, $options);
		$output->info('Cleanup complete', true);
	}

}
