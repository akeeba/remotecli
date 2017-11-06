<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionCurl extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 105;
		if (empty($message))
		{
			$message = 'cURL error';
		}
		parent::__construct($message);
	}
}