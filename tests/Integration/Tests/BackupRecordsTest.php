<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Integration\Tests;

use Akeeba\RemoteCLI\Tests\Integration\E2ETestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Listing, inspecting and removing backup records.
 *
 * @since 3.2.0
 */
class BackupRecordsTest extends E2ETestCase
{
	#[TestDox('Backup records are listed newest first')]
	public function testListBackups(): void
	{
		$result = $this->runWithToken(['listbackups', '-m', '--quiet']);

		$this->assertSucceeded($result);

		$lines = array_values(array_filter(explode("\n", trim($result->stdout))));

		$this->assertCount(2, $lines);
		$this->assertStringContainsString('A three part backup', $lines[0]);
		$this->assertStringContainsString('A single part backup', $lines[1]);
	}

	#[TestDox('--limit caps how many records are listed')]
	public function testListBackupsLimit(): void
	{
		$result = $this->runWithToken(['listbackups', '--limit=1', '-m', '--quiet']);

		$this->assertSucceeded($result);
		$this->assertCount(1, array_values(array_filter(explode("\n", trim($result->stdout)))));
	}

	#[TestDox('A single record can be inspected by its ID')]
	public function testBackupInfo(): void
	{
		$result = $this->runWithToken(['backupinfo', '--id=2', '-m', '--quiet']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('A three part backup', $result->output());
	}

	#[TestDox('Asking for a record which does not exist is an error, not an empty answer')]
	public function testBackupInfoForAMissingRecord(): void
	{
		$result = $this->runWithToken(['backupinfo', '--id=9999']);

		$this->assertNotSame(0, $result->exitCode, $result->describe());
	}

	#[TestDox('backupinfo without an ID is refused')]
	public function testBackupInfoWithoutAnId(): void
	{
		$result = $this->runWithToken(['backupinfo']);

		// Error #31: you must specify a numeric backup ID.
		$this->assertFailedWithCode($result, 31);
	}

	#[TestDox('Deleting a record removes it from the listing')]
	public function testDeleteRemovesTheRecord(): void
	{
		$this->assertSucceeded($this->runWithToken(['delete', '--id=1']));

		$listing = $this->runWithToken(['listbackups', '-m', '--quiet']);

		$this->assertSucceeded($listing);
		$this->assertStringNotContainsString('A single part backup', $listing->output());
		$this->assertStringContainsString('A three part backup', $listing->output());
	}

	#[TestDox('Deleting the files keeps the record but marks it obsolete')]
	public function testDeleteFilesKeepsTheRecord(): void
	{
		$this->assertSucceeded($this->runWithToken(['deletefiles', '--id=1']));

		$listing = $this->runWithToken(['listbackups', '-m', '--quiet']);

		$this->assertSucceeded($listing);
		$this->assertStringContainsString('A single part backup', $listing->output());
		$this->assertStringContainsString('obsolete', $listing->output());
	}
}
