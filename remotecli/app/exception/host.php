<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteAppExceptionHost extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 35;
		if (empty($message))
		{
			$message = 'You did not specify a host name';
		}
		parent::__construct($message);
	}
}