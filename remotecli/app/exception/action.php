<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteAppExceptionAction extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 38;
		if (empty($message))
		{
			$message = 'Invalid action';
		}
		parent::__construct($message);
	}
}