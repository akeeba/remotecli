<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionNoupdates extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 1;
		if (empty($message))
		{
			$message = 'No updates are available at this time';
		}
		parent::__construct($message);
	}
}