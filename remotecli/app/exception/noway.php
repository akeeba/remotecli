<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteAppExceptionNoway extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 36;
		if (empty($message))
		{
			$message = 'Your server does not seem to be compatible with Remote Control';
		}
		parent::__construct($message);
	}
}