<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;
use RuntimeException;

class InvalidJSONBody extends RuntimeException
{
	public function __construct($code = 21, Exception $previous = null)
	{
		$message = 'Invalid response body. This should never happen!';

		parent::__construct($message, $code, $previous);
	}

}
