<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

class License extends AbstractCommand
{
	public function execute(): void
	{
		echo file_get_contents(__DIR__ . '/../../LICENSE.txt');
	}
}
