<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionCantdeleterecord extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 107;
		if (empty($message))
		{
			$message = 'Could not delete backup record';
		}
		parent::__construct($message);
	}
}