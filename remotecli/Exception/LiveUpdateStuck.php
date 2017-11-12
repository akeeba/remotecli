<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use RuntimeException;
use Throwable;

class LiveUpdateStuck extends RuntimeException
{
	public function __construct($extra = '', $code = 113, Throwable $previous = null)
	{
		$message = 'Akeeba Live Update reports that it\'s stuck trying to load update information.';

		if (!empty($extra))
		{
			$message .= ' ' . $extra;
		}

		parent::__construct($message, $code, $previous);
	}

}
