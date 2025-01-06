<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

use Akeeba\BackupJsonApi\Exception\NoBackupID;
use Akeeba\RemoteCLI\Application\Input\Cli;

class BackupInfo extends AbstractCommand
{
	public function execute(): void
	{
		$this->assertConfigured();

		// Get and print the backup records
		$id   = $this->input->getInt('id');
		$json = $this->input->getBool('json', false);

		$backup = $this->getApiObject()->getBackup($id);

		if (!$json)
		{
			$this->output->header("Statistic info for backup #" . $id);
		}

		if (empty($backup))
		{
			$this->logger->warning('No backup records was found');

			return;
		}

		$status = ($backup->status == 'complete') && !($backup->filesexist) ? 'obsolete' : $backup->status;

		if ($status === 'obsolete' && !empty($backup->remote_filename))
		{
			$status = 'remote';
		}

		// If multipart is 0 it means that's a single backup archive
		$parts = (!$backup->multipart ? 1 : $backup->multipart);

		if (!$json)
		{
			$line = sprintf(
				'%6u|%s|%-8s|%s|%s|%d|%d|%s|%s',
				$backup->id,
				$backup->backupstart,
				$status,
				$backup->description,
				$backup->profile_id,
				$parts,
				$backup->size ?? 0,
				$backup->absolute_path ?? '',
				$backup->remote_filename ?? ''
			);

			$this->logger->debug($line);
			$this->output->info($line, true);
		}
		else
		{
			$thisOut      = [
				'id'              => $backup->id,
				'backupstart'     => $backup->backupstart,
				'status'          => $status,
				'description'     => $backup->description,
				'profile_id'      => $backup->profile_id,
				'parts'           => $parts,
				'size'            => $backup->size ?? 0,
				'absolute_path'   => $backup->absolute_path ?? '',
				'remote_filename' => $backup->remote_filename ?? '',
				'part_files'      => [
					basename(
						($backup->remote_filename ?? '') ?: ($backup->remote_filename ?? '')
					),
				],
			];

			if (!in_array($status, ['complete', 'remote']))
			{
				$thisOut['part_files'] = [];
			}
			elseif ($parts > 1)
			{
				$thisOut['part_files'] = $this->getPartFiles($thisOut['part_files'][0], $parts);
			}

			echo json_encode($thisOut, JSON_PRETTY_PRINT);
		}

	}

	/**
	 * Make sure that the user has provided enough and correct configuration for this command to run.
	 *
	 * We are overriding this to run additional checks which make sense in the context of this command.
	 *
	 * @param   Cli  $input  The input object.
	 *
	 * @return  void
	 */
	protected function assertConfigured(): void
	{
		parent::assertConfigured();

		$id = $this->input->getInt('id', -1);

		if ($id <= 0)
		{
			throw new NoBackupID();
		}
	}

	private function getPartFiles(string $filename, int $numParts): array
	{
		$extension = substr($filename, -4);
		$baseName  = substr($filename, 0, -4);
		$out       = [];

		for ($i = 0; $i < $numParts - 1; $i++)
		{
			$out[] = $baseName . substr($extension, 0, 2) . sprintf('%02u', $i);
		}

		$out[] = $filename;

		return $out;
	}
}
