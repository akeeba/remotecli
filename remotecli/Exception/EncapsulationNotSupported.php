<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;
use RuntimeException;

class EncapsulationNotSupported extends RuntimeException
{
	public function __construct($type, $code = 66, Exception $previous = null)
	{
		$message = sprintf('The encapsulation method ‘%s’ is no longer supported. Please use ‘AES128’ instead', $type);

		parent::__construct($message, $code, $previous);
	}
}