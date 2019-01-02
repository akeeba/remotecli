<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use RuntimeException;
use Throwable;

class LiveUpdateDownloadError extends RuntimeException
{
	public function __construct($errorMessage, $code = 115, Throwable $previous = null)
	{
		$message = sprintf('Update download failed with error ‘%s’', $errorMessage);

		parent::__construct($message, $code, $previous);
	}

}
