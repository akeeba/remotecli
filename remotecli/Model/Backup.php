<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Model;


use Akeeba\RemoteCLI\Api\Api;
use Akeeba\RemoteCLI\Api\Options;
use Akeeba\RemoteCLI\Exception\CannotListBackupRecords;
use Akeeba\RemoteCLI\Exception\RemoteError;
use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Output\Output;

class Backup
{
	/**
	 * Take a backup
	 *
	 * @param   Cli      $input    The input object.
	 * @param   Output   $output   The output object.
	 * @param   Options  $options  The API options. The format, verb and endpoint options _may_ be overwritten.
	 *
	 * @return  array  [backupRecordID, archive]
	 */
	public function backup(Cli $input, Output $output, Options $options)
	{
		$backupRecordID = 0;
		$archive        = '';
		$backupID       = null;

		$info = $this->startBackup($input, $output, $options);

		$data = $info['data'];

		while (isset($data->body->data->HasRun) && $data->body->data->HasRun)
		{
			$backupID = $info['backupID'];
			$info     = $this->stepBackup($output, $options,$backupID);

			$data = $info['data'];
		}

		if ($data->body->status != 200)
		{
			throw new RemoteError('Error ' . $data->body->status . ": " . $data->body->data);
		}

		if (isset($info['backupRecordID']))
		{
			$backupRecordID = $info['backupRecordID'];
		}

		if (isset($info['archive']))
		{
			$archive = $info['archive'];
		}

		$output->header('Backup finished successfully');
		$output->debug('Backup record ID: ' . $backupRecordID);
		$output->debug('Archive name: ' . $archive);

		return [$backupRecordID, $archive];
	}

	public function getBackups(Cli $input, Output $output, Options $options)
	{
		$api = new Api($options, $output);

		// From is >= 1
		$from = max(0, $input->getInt('from', 0));

		// Limit is between 10 and 200. Also support the old --to parameter.
		$limit = $input->getInt('to', 50);
		$limit = $input->getInt('limit', $limit);
		$limit = min(max(1, $limit), 200);

		$data = $api->doQuery('listBackups', [
			'from'  => $from,
			'limit' => $limit,
		]);

		if ($data->body->status != 200)
		{
			throw new CannotListBackupRecords("Could not list backup records");
		}

		return $data->body->data;
	}

	public function getBackup(Cli $input, Output $output, Options $options)
	{
		$api = new Api($options, $output);

		$id = $input->getInt('id', 0);

		$data = $api->doQuery('getBackupInfo', ['backup_id' => $id]);

		if ($data->body->status != 200)
		{
			throw new CannotListBackupRecords("Could not list backup records");
		}

		return $data->body->data;
	}

	public function startBackup(Cli $input, Output $output, Options $options)
	{
		$api            = new Api($options, $output);
		$profile        = (int) ($input->getInt('profile', 1));
		$description    = $input->getString('description', "Remote backup");
		$comment        = $input->getHtml('comment', '');

		$config = [
			'profile'     => $profile,
			'description' => $description,
			'comment'     => $comment,
		];

		$data = $api->doQuery('startBackup', $config);
		$info = $this->handleAPIResponse($data, $output);

		$info['data'] = $data;

		return $info;
	}

	public function stepBackup(Output $output, Options $options, $backupID)
	{
		$api    = new Api($options, $output);
		$params = [];

		if ($backupID)
		{
			$params['backupid'] = $backupID;
		}

		$data = $api->doQuery('stepBackup', $params);
		$info = $this->handleAPIResponse($data, $output);

		$info['data'] = $data;

		return $info;
	}

	protected function handleAPIResponse($data, Output $output)
	{
		$backupID       = null;
		$backupRecordID = 0;
		$progress       = 0;
		$archive        = '';

		if ($data->body->status != 200)
		{
			throw new RemoteError('Error ' . $data->body->status . ": " . $data->body->data);
		}

		if (isset($data->body->data->BackupID))
		{
			$backupRecordID = $data->body->data->BackupID;
			$output->debug('Got backup record ID: ' . $backupRecordID);
		}

		if (isset($data->body->data->backupid))
		{
			$backupID = $data->body->data->backupid;
			$output->debug('Got backupID: ' . $backupID);
		}

		if (isset($data->body->data->Archive))
		{
			$archive = $data->body->data->Archive;
			$output->debug('Got archive name: ' . $archive);
		}

		if (isset($data->body->data->Progress))
		{
			$progress = $data->body->data->Progress;
		}

		$output->header('Got backup tick');
		$output->info("Progress: {$progress}%");
		$output->info("Domain  : {$data->body->data->Domain}");
		$output->info("Step    : {$data->body->data->Step}");
		$output->info("Substep : {$data->body->data->Substep}");

		if (!empty($data->body->data->Warnings))
		{
			foreach ($data->body->data->Warnings as $warning)
			{
				$output->warning("Warning : $warning");
			}
		}

		$output->info('');

		$info = [
			'backupID'       => $backupID,
			'backupRecordID' => $backupRecordID,
			'archive'        => $archive
		];

		return $info;
	}
}
