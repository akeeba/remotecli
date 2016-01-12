<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteAppExceptionDlpath extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 33;
		if (empty($message))
		{
			$message = 'You must specify a path to download the files to';
		}
		parent::__construct($message);
	}
}