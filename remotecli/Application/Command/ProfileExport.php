<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

use Akeeba\BackupJsonApi\Exception\NoProfileID;

class ProfileExport extends AbstractCommand
{
	public function execute(): void
	{
		$this->assertConfigured();

		// Get and print profile configuration
		$id           = $this->input->getInt('id', 0);
		$profile_data = $this->getApiObject()->exportConfiguration($id);

		if (in_array('--', $this->input->getArguments()))
		{
			echo json_encode($profile_data);

			return;
		}

		$this->output->header(sprintf("Export data for profile %d", $id));

		$filePath = $this->input->getPath('file', getcwd() . '/profile.json');
		$written  = @file_put_contents($filePath, json_encode($profile_data));

		if ($written)
		{
			$this->output->info(
				sprintf('Exported configuration to %s', $filePath)
			);

			return;
		}

		$this->output->error(
			sprintf('Cannot write into %s', $filePath)
		);
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
