<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;

class EncapsulationNotSupported extends ApiException
{
	public function __construct($type, $code = 66, Exception $previous = null)
	{
		$message = sprintf('The encapsulation method ‘%s’ is no longer supported. Please use ‘AES128’ instead', $type);

		parent::__construct($message, $code, $previous);
	}
}
