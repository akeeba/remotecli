<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

use Akeeba\BackupJsonApi\Exception\LiveUpdateStability;

class Update extends AbstractCommand
{
	public function execute(): void
	{
		$this->assertConfigured();

		$apiObject = $this->getApiObject();

		$updateInfo = $apiObject->getUpdateInformation();

		$this->output->header('Update found');
		$this->output->info("Version   : {$updateInfo->version}");
		$this->output->info("Date      : {$updateInfo->date}");
		$this->output->info("Stability : {$updateInfo->stability}");

		// Is it stable enough?
		$minStability = $this->input->getCmd('minimum-stability', 'alpha');
		$minStability = !in_array($minStability, ['alpha', 'beta', 'rc', 'stable']) ? 'alpha' : $minStability;
		$stabilities = array('alpha' => 0, 'beta' => 30, 'rc' => 60, 'stable' => 100);
		$min         = array_key_exists($minStability, $stabilities) ? $stabilities[$minStability] : 0;
		$cur         = array_key_exists($updateInfo->stability, $stabilities) ? $stabilities[$updateInfo->stability] : 0;

		if ($cur < $min)
		{
			throw new LiveUpdateStability("The available version does not fulfil your minimum stability preferences");
		}

		$this->output->header('Downloading update');
		$apiObject->downloadUpdate();
		$this->output->info('Update downloaded to your server', true);

		$this->output->header('Extracting update');
		$apiObject->extractUpdate();
		$this->output->info('Update extracted to your server', true);

		$this->output->header('Installing update');
		$apiObject->installUpdate();
		$this->output->info('updateInstall', true);

		$this->output->header('Cleaning up');
		$apiObject->cleanupUpdate();
		$this->output->info('Cleanup complete', true);
	}

}
