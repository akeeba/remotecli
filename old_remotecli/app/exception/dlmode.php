<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteAppExceptionDlmode extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 32;
		if (empty($message))
		{
			$message = 'You must specify a download mode (http, curl or chunk)';
		}
		parent::__construct($message);
	}
}