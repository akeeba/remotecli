<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\Exception;

use Exception;

class InvalidEncapsulatedJSON extends ApiException
{
	public function __construct(string $type, int $code = 23, Exception $previous = null)
	{
		$message = sprintf('Invalid JSON data returned from the server: ‘%s’.', $type);

		parent::__construct($message, $code, $previous);
	}
}