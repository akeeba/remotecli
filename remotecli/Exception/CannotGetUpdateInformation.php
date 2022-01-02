<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


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
