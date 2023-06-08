<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

class Download extends AbstractCommand
{
	public function prepare(): void
	{
		parent::prepare();

		if ($this->input->getBool('D', false))
		{
			$this->input->set('delete', true);
		}
	}

	public function execute(): void
	{
		$this->assertConfigured();

		$downloadParameters = $this->getDownloadOptions();

		$this->logger->info('Downloading the backup archive file(s).');

		$this->getApiObject()->download($downloadParameters);

		$this->logger->info(sprintf('Downloaded the backup archive file(s) for backup record #%s', $downloadParameters->id));

		if ($downloadParameters->delete)
		{
			$this->logger->info('Deleting the backup archive file(s) from the server.');

			$this->getApiObject()->deleteFiles($downloadParameters->id);

			$this->logger->info(sprintf('Deleted the backup archive file(s) for backup record #%s', $downloadParameters->id));
		}
	}

}
