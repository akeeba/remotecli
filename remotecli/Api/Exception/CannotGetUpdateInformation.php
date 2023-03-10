<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Api\Exception;


use RuntimeException;
use Throwable;

class CannotGetUpdateInformation extends RuntimeException
{
	public function __construct(int $code = 111, Throwable $previous = null)
	{
		$message = 'Cannot retrieve update information.';

		parent::__construct($message, $code, $previous);
	}

}
