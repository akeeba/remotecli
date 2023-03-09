<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\HighLevel;

use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\Exception\CannotDeleteFiles;
use Akeeba\RemoteCLI\Api\Exception\NoBackupID;

class DeleteFiles
{
	public function __construct(private Connector $connector)
	{
	}

	public function __invoke(int $id): void
	{
		if ($id <= 0)
		{
			throw new NoBackupID();
		}

		$data = $this->connector->doQuery('deleteFiles', [
			'backup_id' => $id
		]);

		if ($data->body->status != 200)
		{
			throw new CannotDeleteFiles($id, $data->body->status, $data->body->data);
		}
	}
}