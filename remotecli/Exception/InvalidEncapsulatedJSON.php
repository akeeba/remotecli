<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;

class InvalidEncapsulatedJSON extends ApiException
{
	public function __construct($type, $code = 23, Exception $previous = null)
	{
		$message = sprintf('Invalid JSON data returned from the server: ‘%s’.', $type);

		parent::__construct($message, $code, $previous);
	}
}
