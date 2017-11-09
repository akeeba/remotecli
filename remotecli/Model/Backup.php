<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Model;


use Akeeba\RemoteCLI\Api\Api;
use Akeeba\RemoteCLI\Api\Options;
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
		$api = new Api($options, $output);
		$profile        = (int) ($input->getInt('profile', 1));
		$description    = $input->getString('description', "Remote backup");
		$comment        = $input->getHtml('comment', '');
		$backupRecordID = 0;
		$archive        = '';
		$progress       = 0;
		$backupID       = null;

		$config = [
			'profile'     => $profile,
			'description' => $description,
			'comment'     => $comment,
		];
		$data = $api->doQuery('startBackup', $config);

		while ($data->body->data->HasRun)
		{
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

			$params = array();

			if (!empty($backupID))
			{
				$params['backupid'] = $backupID;
			}

			$data = $api->doQuery('stepBackup', $params);
		}

		$output->header('Backup finished successfully');
		$output->debug('Backup record ID: ' . $backupRecordID);
		$output->debug('Archive name: ' . $archive);

		return [$backupRecordID, $archive];
	}
}
