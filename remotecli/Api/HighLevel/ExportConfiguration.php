<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\HighLevel;

use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\Exception\NoProfileID;

class ExportConfiguration
{
	public function __construct(private Connector $connector)
	{
	}

	public function __invoke(int $id = -1): object
	{
		if ($id <= 0)
		{
			throw new NoProfileID();
		}

		$data = $this->connector->doQuery('exportConfiguration', ['profile' => $id]);

		return $data->body->data;
	}
}
