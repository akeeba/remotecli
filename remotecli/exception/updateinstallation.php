<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionUpdateinstallation extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 117;
		if (empty($message))
		{
			$message = 'Error installing the update on the remote server';
		}
		parent::__construct($message);
	}
}