<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Exception;

class NoWayToConnect extends ApiException
{
	public function __construct(int $code = 36, Exception $previous = null)
	{
		$message = 'We cannot find a way to connect to your server. It seems that your server is incompatible with Akeeba Remote Control CLI.';

		parent::__construct($message, $code, $previous);
	}
}
