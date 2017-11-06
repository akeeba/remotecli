<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionNofiles extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 103;
		if (empty($message))
		{
			$message = 'No files to download!';
		}
		parent::__construct($message);
	}
}