<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;
use RuntimeException;

class NoConfiguredHost extends RuntimeException
{
	public function __construct($code = 35, Exception $previous = null)
	{
		$message = 'You did not specify a host name.';

		parent::__construct($message, $code, $previous);
	}

}
