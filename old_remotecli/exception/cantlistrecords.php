<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionCantlistrecords extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 109;
		if (empty($message))
		{
			$message = 'Could not list backup records';
		}
		parent::__construct($message);
	}
}