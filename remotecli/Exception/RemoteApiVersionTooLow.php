<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright Copyright (c)2008-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;

class RemoteApiVersionTooLow extends ApiException
{
	public function __construct($code = 102, Exception $previous = null)
	{
		$message = 'You need to install a newer version of Akeeba Backup / Akeeba Solo on your site';

		parent::__construct($message, $code, $previous);
	}
}
