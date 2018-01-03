<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright Copyright (c)2008-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;
use RuntimeException;

class CannotWriteFile extends RuntimeException
{
	public function __construct($filePath, $code = 104, Exception $previous = null)
	{
		$message = sprintf('Cannot open file ‘%s’ for writing.', $filePath);

		parent::__construct($message, $code, $previous);
	}

}
