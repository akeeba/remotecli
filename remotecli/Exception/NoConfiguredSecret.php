<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;
use RuntimeException;

class NoConfiguredSecret extends RuntimeException
{
	public function __construct($code = 37, Exception $previous = null)
	{
		$message = 'You did not specify a secret key.';

		parent::__construct($message, $code, $previous);
	}

}
