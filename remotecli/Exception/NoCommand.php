<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Throwable;

class NoCommand extends \RuntimeException
{
	public function __construct($code = 11, Throwable $previous = null)
	{
		$message = 'You have not specified a command to run.';

		parent::__construct($message, $code, $previous);
	}

}
