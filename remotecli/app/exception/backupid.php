<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteAppExceptionBackupid extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 31;
		if (empty($message))
		{
			$message = 'You must specify a numeric backup ID';
		}
		parent::__construct($message);
	}
}