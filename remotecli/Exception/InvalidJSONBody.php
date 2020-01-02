<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;

class InvalidJSONBody extends ApiException
{
	public function __construct($code = 21, Exception $previous = null)
	{
		$message = 'Invalid response body. Please make sure that a PHP cryptography module (OpenSSL or mcrypt) is installed and enabled on your machine and the remote machine.';

		parent::__construct($message, $code, $previous);
	}

}
