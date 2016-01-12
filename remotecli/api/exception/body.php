<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteApiExceptionBody extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 21;
		if (empty($message))
		{
			$message = 'Invalid response body. This should never happen!';
		}
		parent::__construct($message);
	}
}