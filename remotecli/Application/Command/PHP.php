<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Command;

use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Output\Output;

class PHP extends AbstractCommand
{
	protected $requiredExtensions = ['curl'];

	public function execute(Cli $input, Output $output): void
	{
		$phpVersion     = PHP_VERSION;
		$rawExtensions  = get_loaded_extensions(false);
		$extensions     = implode("\n", $rawExtensions);
		$zendExtensions = implode("\n", get_loaded_extensions(true));

		$meetRequirements = true;

		foreach ($this->requiredExtensions as $req)
		{
			$meetRequirements &= in_array($req, $rawExtensions);
		}

		$requirementsText = $meetRequirements ? 'OK' : 'FAIL';

		echo <<< END
Running on PHP version $phpVersion

Requirements check: $requirementsText

Activated PHP extensions:
$extensions

Activated Zend extensions:
$zendExtensions

END;

	}

}
