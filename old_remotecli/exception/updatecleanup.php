<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionUpdatecleanup extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 118;
		if (empty($message))
		{
			$message = 'Error cleaning up after installing the update';
		}
		parent::__construct($message);
	}
}