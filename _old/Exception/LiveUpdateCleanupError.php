<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\OLD\RemoteCLI\Exception;


use RuntimeException;
use Throwable;

class LiveUpdateCleanupError extends RuntimeException
{
	public function __construct(string $errorMessage, int $code = 118, Throwable $previous = null)
	{
		$message = sprintf('Update failed to clean up with error ‘%s’', $errorMessage);

		parent::__construct($message, $code, $previous);
	}

}
