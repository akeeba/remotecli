<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Exception;


use RuntimeException;
use Throwable;

class LiveUpdateStability extends RuntimeException
{
	public function __construct(int $code = 114, Throwable $previous = null)
	{
		$message = 'The available update is less stable than the minimum stability you have chosen for updates. As a result the update will not proceed.';

		parent::__construct($message, $code, $previous);
	}

}
