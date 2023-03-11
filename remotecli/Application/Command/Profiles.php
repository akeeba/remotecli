<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Command;


use Akeeba\RemoteCLI\Api\HighLevel\GetProfiles as ProfilesModel;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;
use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Output\Output;

class Profiles extends AbstractCommand
{
	public function execute(): void
	{
		$this->assertConfigured();

		// Get and print the backup profiles
		$profiles = $this->getApiObject()->getProfiles();

		$this->output->header("List of profiles");

		if (empty($profiles))
		{
			$this->logger->warning('No backup profiles were found');

			return;
		}

		foreach ($profiles as $profile)
		{
			$line = sprintf('%4u|%s', $profile->id, $profile->name);

			$this->logger->debug($line);
			$this->output->info($line, true);
		}
	}
}
