<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use Throwable;

class InvalidCommand extends \RuntimeException
{
	public function __construct($command, $code = 10, Throwable $previous = null)
	{
		$message = sprintf('Invalid command ‘%s’.', $command);

		parent::__construct($message, $code, $previous);
	}

}
