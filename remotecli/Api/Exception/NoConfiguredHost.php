<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Api\Exception;


use Exception;
use RuntimeException;

class NoConfiguredHost extends RuntimeException
{
	public function __construct(int $code = 35, Exception $previous = null)
	{
		$message = 'You did not specify a host name.';

		parent::__construct($message, $code, $previous);
	}

}
