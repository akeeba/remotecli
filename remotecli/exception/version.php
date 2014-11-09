<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionVersion extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 102;
		if (empty($message))
		{
			$message = 'You need to install a newer version of Akeeba Backup on your site';
		}
		parent::__construct($message);
	}
}