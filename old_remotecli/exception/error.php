<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionError extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 101;
		if (empty($message))
		{
			$message = 'Akeeba Backup error';
		}
		parent::__construct($message);
	}
}