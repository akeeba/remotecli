<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteApiExceptionJson extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 23;
		if (empty($message))
		{
			$message = 'Could not decode server\'s response';
		}
		parent::__construct($message);
	}
}