<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\HighLevel;

use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\Exception\CannotListBackupRecords;

class GetBackups
{
	public function __construct(private Connector $connector)
	{
	}

	public function __invoke(int $from = 0, $limit = 200): array
	{
		// from in [200, ∞), limit in [1, 200]
		$from = max(0, $from);
		$limit = min(max(1, $limit), 200);

		$data = $this->connector->doQuery('listBackups', [
			'from'  => $from,
			'limit' => $limit,
		]);

		if ($data->body->status != 200)
		{
			throw new CannotListBackupRecords();
		}

		return $data->body->data ?: [];
	}
}