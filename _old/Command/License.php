<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\OLD\RemoteCLI\Command;


use Akeeba\OLD\RemoteCLI\Input\Cli;
use Akeeba\OLD\RemoteCLI\Output\Output;

class License extends AbstractCommand
{
	public function execute(Cli $input, Output $output): void
	{
		echo file_get_contents(__DIR__ . '/../LICENSE.txt');
	}

}
