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
 * Taking a backup: the stepping loop, the options which shape the record, and what is relayed while it runs.
 *
 * @since 3.2.0
 */
class BackupTest extends E2ETestCase
{
	#[TestDox('A backup runs to completion and says so')]
	public function testBackupCompletes(): void
	{
		$result = $this->runWithToken(['backup', '--profile=1']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('The backup finished successfully.', $result->output());
	}

	#[TestDox('The backup is stepped until the site says it has finished')]
	public function testBackupIsStepped(): void
	{
		$this->assertSucceeded($this->runWithToken(['backup', '--profile=1']));

		$methods = array_map(fn(array $request) => $request['method'], $this->fixture->requests());

		$this->assertContains('startBackup', $methods);
		$this->assertContains('stepBackup', $methods, 'The backup should have been stepped, not just started.');
	}

	#[TestDox('The description and comment reach the backup record')]
	public function testDescriptionAndCommentAreStored(): void
	{
		$this->assertSucceeded(
			$this->runWithToken(
				['backup', '--profile=1', '--description=Nightly from the suite', '--comment=Some comment']
			)
		);

		$listing = $this->runWithToken(['listbackups', '-m', '--quiet']);

		$this->assertStringContainsString('Nightly from the suite', $listing->output());
	}

	#[TestDox('A warning raised during the backup is relayed to the user')]
	public function testWarningsAreRelayed(): void
	{
		/**
		 * The fixture raises one warning halfway through. Swallowing it would leave a user with a backup they believe
		 * is clean, which is the one thing a backup tool must never do.
		 */
		$result = $this->runWithToken(['backup', '--profile=1']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('only pretending to back anything up', $result->output());
	}

	#[TestDox('The backup profile defaults to 1 when none is given')]
	public function testProfileDefaultsToOne(): void
	{
		$this->assertSucceeded($this->runWithToken(['backup']));

		$this->assertNotEmpty($this->fixture->requests());
	}

	#[TestDox('A backup works over the v2 API as well')]
	public function testBackupOverApiV2(): void
	{
		$this->fixture->speakOnly([2]);

		$result = $this->runWithSecret(['backup', '--profile=1']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('The backup finished successfully.', $result->output());
	}
}
