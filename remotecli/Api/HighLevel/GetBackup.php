<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\HighLevel;

use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\Exception\CannotListBackupRecords;
use Akeeba\RemoteCLI\Api\Exception\NoSuchBackupRecord;

class GetBackup
{
	public function __construct(private Connector $connector)
	{
	}

	public function __invoke(int $id = 0): object
	{
		$data = $this->connector->doQuery('getBackupInfo', ['backup_id' => $id]);

		if ($data->body->status != 200)
		{
			throw new NoSuchBackupRecord();
		}

		return $data->body->data;
	}


}