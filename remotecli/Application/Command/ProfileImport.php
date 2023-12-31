<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

use Akeeba\BackupJsonApi\Exception\NoProfileData;

class ProfileImport extends AbstractCommand
{
	private ?string $data;

	public function execute(): void
	{
		$this->assertConfigured();

		$this->getApiObject()->importConfiguration($this->data);

		$this->output->info(sprintf("Profile imported"));
	}

	protected function assertConfigured(): void
	{
		parent::assertConfigured();

		$data = null;
		$dataFile = $this->input->getPath('file', null);

		if (in_array('--', $this->input->getArguments()))
		{
			while (!feof(STDIN))
			{
				$data .= fread(STDIN, 1048756);
			}
		}
		elseif(!empty($dataFile) && @file_exists($dataFile) && @is_file($dataFile) && @is_readable($dataFile))
		{
			$data = @file_get_contents($dataFile) ?: null;
		}
		else
		{
			$data = $this->input->get('data', null, 'raw');
		}

		if (!$data)
		{
			throw new NoProfileData();
		}

		$this->data = $data;
	}
}
