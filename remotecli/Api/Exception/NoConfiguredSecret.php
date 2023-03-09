<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Api\Exception;


use Exception;
use RuntimeException;

class NoConfiguredSecret extends RuntimeException
{
	public function __construct(int $code = 37, Exception $previous = null)
	{
		$message = 'You did not specify a secret key.';

		parent::__construct($message, $code, $previous);
	}

}
