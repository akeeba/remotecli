<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use RuntimeException;
use Throwable;

class NoFilesInBackupRecord extends RuntimeException
{
	public function __construct(int $id, int $code = 103, Throwable $previous = null)
	{
		$message = sprintf("The archive file(s) for backup record #%d are not available on the remote server. Please check if this is an obsolete backup record; or if the files have been sent to a different location and removed from the server; or if the backup was taken with an archiver engine which does not generate backup archives, such as DirectFTP.", $id);

		parent::__construct($message, $code, $previous);
	}

}
