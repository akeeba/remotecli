<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use RuntimeException;
use Throwable;

class CannotDeleteRecord extends RuntimeException
{
	public function __construct(int $id, int $code = 107, Throwable $previous = null)
	{
		$message = sprintf("Cannot delete backup record %d.", $id);

		parent::__construct($message, $code, $previous);
	}

}
