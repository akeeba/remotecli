<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

use Akeeba\BackupJsonApi\Exception\NoBackupID;
use Akeeba\RemoteCLI\Application\Input\Cli;

class Deletefiles extends AbstractCommand
{
	public function execute(): void
	{
		$this->assertConfigured();

		$id = $this->input->getInt('id');

		$this->getApiObject()->deleteFiles($id);

		$this->logger->info(sprintf('Deleted the backup archive files of record #%d', $id));
	}

	/**
	 * Make sure that the user has provided enough and correct configuration for this command to run.
	 *
	 * We are overriding this to run additional checks which make sense in the context of this command.
	 *
	 * @param   Cli  $input  The input object.
	 *
	 * @return  void
	 */
	protected function assertConfigured(): void
	{
		parent::assertConfigured();

		$id = $this->input->getInt('id', -1);

		if ($id <= 0)
		{
			throw new NoBackupID();
		}
	}
}
