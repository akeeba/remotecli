<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionFilewrite extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 104;
		if (empty($message))
		{
			$message = 'File writing error';
		}
		parent::__construct($message);
	}
}