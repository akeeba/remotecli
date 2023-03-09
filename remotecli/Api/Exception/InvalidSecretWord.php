<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\Exception;

use Exception;

class InvalidSecretWord extends ApiException
{
	public function __construct(int $code = 42, Exception $previous = null)
	{
		$message = 'Authentication error (invalid Secret Word). Please check the secret word, make sure it doesn\'t have any whitespace you missed. Clear any site or external caches, making sure Akeeba Backup\'s URL isn\'t cached.';

		parent::__construct($message, $code, $previous);
	}
}
