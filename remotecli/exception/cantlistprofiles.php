<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionCantlistprofiles extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 108;
		if (empty($message))
		{
			$message = 'Could not list profiles';
		}
		parent::__construct($message);
	}
}