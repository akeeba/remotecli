<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use RuntimeException;
use Throwable;

class NoUpdates extends RuntimeException
{
	public function __construct($code = 1, Throwable $previous = null)
	{
		$message = 'There are no available updates to your Akeeba Backup / Akeeba Solo installation.';

		parent::__construct($message, $code, $previous);
	}

}
