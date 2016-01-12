<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteAppExceptionSecret extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 37;
		if (empty($message))
		{
			$message = 'You did not specify a secret key';
		}
		parent::__construct($message);
	}
}