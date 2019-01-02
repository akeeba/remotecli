<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;
use RuntimeException;

class CannotReadBackupRecord extends RuntimeException
{
	public function __construct($id, $code = 110, Exception $previous = null)
	{
		$message = sprintf('Cannot read the information of backup record %d.', $id);

		parent::__construct($message, $code, $previous);
	}

}
