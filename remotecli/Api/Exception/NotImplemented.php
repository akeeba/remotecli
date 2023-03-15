<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\Exception;

class NotImplemented extends ApiException
{
	public function __construct(string $method = '', int $code = 44, \Exception $previous = null)
	{
		$message = sprintf('The method %s is no longer implemented by the Akeeba Remote JSON API on your server.', $method);

		parent::__construct($message, $code, $previous);
	}

}