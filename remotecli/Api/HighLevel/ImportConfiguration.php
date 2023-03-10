<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\HighLevel;

use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\Exception\NoProfileData;

class ImportConfiguration
{
	public function __construct(private Connector $connector)
	{
	}

	public function __invoke(string $jsonData): array
	{
		if (!$jsonData)
		{
			throw new NoProfileData();
		}

		$decodedData = json_decode($jsonData);

		$response = $this->connector->doQuery('importConfiguration', ['profile' => 0, 'data' => $decodedData]);

		return $response->body->data;
	}
}
