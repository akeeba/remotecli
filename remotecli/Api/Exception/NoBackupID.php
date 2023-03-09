<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\Exception;

use Exception;
use RuntimeException;

class NoBackupID extends RuntimeException
{
	public function __construct(int $code = 31, Exception $previous = null)
	{
		$message = 'You must specify a numeric backup ID';

		parent::__construct($message, $code, $previous);
	}

}
