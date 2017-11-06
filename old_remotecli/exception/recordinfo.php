<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionRecordinfo extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 110;
		if (empty($message))
		{
			$message = 'Could not get backup record information';
		}
		parent::__construct($message);
	}
}