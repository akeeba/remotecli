<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Api\Exception;


use RuntimeException;
use Throwable;

class LiveUpdateInstallError extends RuntimeException
{
	public function __construct(string $errorMessage, int $code = 117, Throwable $previous = null)
	{
		$message = sprintf('Update package failed to install with error ‘%s’', $errorMessage);

		parent::__construct($message, $code, $previous);
	}

}
