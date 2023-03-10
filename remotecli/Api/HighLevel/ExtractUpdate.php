<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\HighLevel;

use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\Exception\LiveUpdateExtractError;

class ExtractUpdate
{
	public function __construct(private Connector $connector)
	{
	}

	public function __invoke(): void
	{
		$data = $this->connector->doQuery('updateExtract', []);

		if ($data->body->status != 200)
		{
			throw new LiveUpdateExtractError($data->body->data);
		}
	}
}