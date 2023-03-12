<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\Exception;

use JetBrains\PhpStorm\Pure;

class UnknownMethod extends ApiException
{
	#[Pure]
	public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
	{
		$message = $message ?: 'The server replied that it does not know of the API method we requested. Is your installation broken?';

		parent::__construct($message, $code, $previous);
	}

}