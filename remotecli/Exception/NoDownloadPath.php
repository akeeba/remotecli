<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;
use RuntimeException;

class NoDownloadPath extends RuntimeException
{
	public function __construct(int $code = 33, Exception $previous = null)
	{
		$message = 'You must specify a path to download the files to.';

		parent::__construct($message, $code, $previous);
	}

}
