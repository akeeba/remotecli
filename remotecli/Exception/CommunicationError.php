<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;
use RuntimeException;

class CommunicationError extends RuntimeException
{
	public function __construct($errCode, $errMessage, $code = 22, Exception $previous = null)
	{
		$message = sprintf('Network error %d with message “%s”. Please check the host name and the status of your network connectivity.', $errCode, $errMessage);

		parent::__construct($message, $code, $previous);
	}

}
