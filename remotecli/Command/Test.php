<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Command;


use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Model\Test as TestModel;
use Akeeba\RemoteCLI\Output\Output;

class Test extends AbstractCommand
{
	public function execute(Cli $input, Output $output)
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
