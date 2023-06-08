<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

class Test extends AbstractCommand
{
	public function execute(): void
	{
		$this->assertConfigured();

		$api         = $this->getApiObject();
		$apiResult   = $api->information();
		$versionInfo = $apiResult->body->data;
		$version     = $versionInfo->component . ' (API level ' . $apiResult->body->data->api . ')';
		$edition     = (($versionInfo->edition ?? '') === 'pro') ? 'Professional ' : 'Core ';
		$edition     = ($versionInfo->edition ?? '') === '' ? '' : $edition;

		$this->output->info("Successful connection to site");
		$this->output->info("Akeeba Backup / Solo $edition$version");
		$this->output->info('');
	}

}
