<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionUpdatedownload extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 115;
		if (empty($message))
		{
			$message = 'Error downloading the update on the remote server';
		}
		parent::__construct($message);
	}
}