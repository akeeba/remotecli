<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\HighLevel;

use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\Exception\CannotListProfiles;

class GetProfiles
{
	public function __construct(private Connector $connector)
	{
	}

	public function __invoke(): array
	{
		$data = $this->connector->doQuery('getProfiles');

		if ($data->body->status != 200)
		{
			throw new CannotListProfiles();
		}

		return $data->body->data;
	}
}
