<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\Exception;

use Exception;
use RuntimeException;

class NoSuchPart extends RuntimeException
{
	public function __construct(int $code = 43, Exception $previous = null)
	{
		$message = 'The part number you specified does not exist in this backup record.';

		parent::__construct($message, $code, $previous);
	}

}
