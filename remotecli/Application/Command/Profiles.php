<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

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
