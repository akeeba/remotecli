<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use RuntimeException;
use Throwable;

class CannotDeleteFiles extends RuntimeException
{
	public function __construct(int $id, int $code = 106, Throwable $previous = null)
	{
		$message = sprintf("Cannot delete backup archive files for backup record %d. Please check if the files have not been already deleted either manually or automatically, e.g. after uploading to a remote location; or whether the backup was taken with an archiver engine which does not generate backup archives, such as DirectFTP.", $id);

		parent::__construct($message, $code, $previous);
	}

}
