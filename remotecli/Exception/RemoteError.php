<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;

class RemoteError extends ApiException
{
	public function __construct($errorMessage, $code = 101, Exception $previous = null)
	{
		$message = sprintf('The remote JSON API on your server reports an error with message ‘%s’', $errorMessage);

		parent::__construct($message, $code, $previous);
	}
}
