<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Command;


use Akeeba\RemoteCLI\Api\Exception\NoProfileData;
use Akeeba\RemoteCLI\Api\HighLevel\GetProfiles as ProfilesModel;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;
use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Output\Output;

class ProfileImport extends AbstractCommand
{
	public function execute(): void
	{
		$this->assertConfigured();

		// Get and print profile configuration
		$data = $this->input->get('data', '', 'raw');
		$this->getApiObject()->importConfiguration($data);

		$this->output->info(sprintf("Profile imported"));
	}

	protected function assertConfigured(): void
	{
		parent::assertConfigured();

		$data = $this->input->get('data', '', 'raw');

		if (!$data)
		{
			throw new NoProfileData();
		}
	}
}
