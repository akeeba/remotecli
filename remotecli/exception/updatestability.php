<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionUpdatestability extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 114;
		if (empty($message))
		{
			$message = 'The available version does not fulfil your minimum stability preferences';
		}
		parent::__construct($message);
	}
}