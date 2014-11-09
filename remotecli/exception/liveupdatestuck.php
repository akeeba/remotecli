<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionLiveupdatestuck extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 113;
		if (empty($message))
		{
			$message = 'Live Update reports that it is stuck. Try using --force=1.';
		}
		parent::__construct($message);
	}
}