<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteAppExceptionDlurl extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 34;
		if (empty($message))
		{
			$message = 'You must provide a download URL for use with cURL';
		}
		parent::__construct($message);
	}
}