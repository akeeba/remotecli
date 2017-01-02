<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteExceptionLiveupdatesupport extends RemoteException
{
	public function __construct($message = null)
	{
		$this->code = 112;
		if (empty($message))
		{
			$message = 'Live Update is not supported on this site.';
		}
		parent::__construct($message);
	}
}