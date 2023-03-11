<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Command;


use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Output\Output;

class License extends AbstractCommand
{
	public function execute(Cli $input, Output $output): void
	{
		echo file_get_contents(__DIR__ . '/../../LICENSE.txt');
	}
}
