<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use RuntimeException;
use Throwable;

class LiveUpdateSupport extends RuntimeException
{
	public function __construct($code = 112, Throwable $previous = null)
	{
		$message = 'Your server does not support Akeeba Live Update.';

		parent::__construct($message, $code, $previous);
	}

}
