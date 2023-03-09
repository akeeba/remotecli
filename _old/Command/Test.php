<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\OLD\RemoteCLI\Command;


use Akeeba\OLD\RemoteCLI\Input\Cli;
use Akeeba\OLD\RemoteCLI\Output\Output;
use Akeeba\RemoteCLI\Api\HighLevel\Information as TestModel;

class Test extends AbstractCommand
{
	public function execute(Cli $input, Output $output): void
	{
		$this->assertConfigured($input);

		$options     = $this->getApiOptions($input);
		$model       = new TestModel();
		$apiResult   = $model->getApiInformation($input, $output, $options);
		$versionInfo = $apiResult->body->data;
		$version     = $versionInfo->component . ' (API level ' . $apiResult->body->data->api . ')';
		$edition     = ($versionInfo->edition == 'pro') ? 'Professional' : 'Core';

		$output->info("Successful connection to site");
		$output->info("Akeeba Backup / Solo $edition $version");
		$output->info('');
	}

}
