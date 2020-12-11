<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;
use RuntimeException;

class NoDownloadURL extends RuntimeException
{
	public function __construct(int $code = 34, Exception $previous = null)
	{
		$message = 'You must provide a download URL for use with cURL';

		parent::__construct($message, $code, $previous);
	}

}
