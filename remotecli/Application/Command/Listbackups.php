<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

class Listbackups extends AbstractCommand
{
	public function execute(): void
	{
		$this->assertConfigured();

		// Get and print the backup records
		$from    = $this->input->getInt('from', 0);
		$limit   = $this->input->getInt('limit', 200);
		$json    = $this->input->getBool('json', false);
		$backups = $this->getApiObject()->getBackups($from, $limit);

		if (!$json)
		{
			$this->output->header("List of backup records");
		}

		if (empty($backups))
		{
			$this->logger->warning('No backup records were found');

			return;
		}

		$outForJson = [];

		foreach ($backups as $record)
		{
			$status = ($record->status == 'complete') && !($record->filesexist) ? 'obsolete' : $record->status;

			if ($status === 'obsolete' && !empty($backup->remote_filename))
			{
				$status = 'remote';
			}

			// If multipart is 0 it means that's a single backup archive
			$parts = (!$record->multipart ? 1 : $record->multipart);

			$line = sprintf('%6u|%s|%-8s|%s|%s|%d|%-8s|%d|%s|%s',
				$record->id,
				$record->backupstart,
				$status,
				$record->description,
				$record->profile_id,
				$parts,
				$record->meta,
				$record->size ?? 0,
				$record->absolute_path ?? '',
				$record->remote_filename ?? ''
			);

			if ($json)
			{
				$thisOut      = [
					'id'              => $record->id,
					'backupstart'     => $record->backupstart,
					'status'          => $status,
					'description'     => $record->description,
					'profile_id'      => $record->profile_id,
					'parts'           => $parts,
					'meta'            => $record->meta,
					'size'            => $record->size ?? 0,
					'absolute_path'   => $record->absolute_path ?? '',
					'remote_filename' => $record->remote_filename ?? '',
					'part_files'      => [
						basename(
							($record->remote_filename ?? '') ?: ($record->remote_filename ?? '')
						),
					],
				];

				if (!in_array($record->meta, ['ok', 'remote']))
				{
					$thisOut['part_files'] = [];
				}
				elseif ($parts > 1)
				{
					$thisOut['part_files'] = $this->getPartFiles($thisOut['part_files'][0], $parts);
				}

				$outForJson[] = $thisOut;
			}
			else
			{
				$this->logger->debug($line);
				$this->output->info($line, true);
			}
		}

		if ($json)
		{
			echo json_encode($outForJson, JSON_PRETTY_PRINT);
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
