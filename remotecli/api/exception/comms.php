<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteApiExceptionComms extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 22;
		if (empty($message))
		{
			$message = 'Communications error; please check the host name and your network status';
		}
		parent::__construct($message);
	}
}