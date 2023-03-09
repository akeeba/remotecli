<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\OLD\RemoteCLI\Exception;


use Exception;
use RuntimeException;

class NoProfileData extends RuntimeException
{
	public function __construct(int $code = 40, Exception $previous = null)
	{
		$message = 'You must supply the profile data that should be imported';

		parent::__construct($message, $code, $previous);
	}

}
