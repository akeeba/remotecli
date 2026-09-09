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
 * Listing backup profiles, and moving one between sites as JSON.
 *
 * @since 3.2.0
 */
class ProfileTest extends E2ETestCase
{
	#[TestDox('Backup profiles are listed with their IDs and names')]
	public function testListProfiles(): void
	{
		$result = $this->runWithToken(['profiles', '-m', '--quiet']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('Full site backup', $result->output());
		$this->assertStringContainsString('Nightly database only', $result->output());
	}

	#[TestDox('A profile exports to JSON on standard output')]
	public function testExportProfileToStdout(): void
	{
		$result = $this->runWithToken(['profileexport', '--id=2', '-m', '--quiet', '--']);

		$this->assertSucceeded($result);

		$exported = json_decode(trim($result->stdout), true);

		$this->assertIsArray($exported, 'The export should be valid JSON. Got: ' . $result->stdout);
		$this->assertSame('Nightly database only', $exported['description']);
	}

	#[TestDox('A profile exports to a file')]
	public function testExportProfileToFile(): void
	{
		$path = '/tmp/arccli-profile.json';

		$this->cli->shell(sprintf('rm -f %s', $path));

		$this->assertSucceeded($this->runWithToken(['profileexport', '--id=1', '--file=' . $path]));

		$contents = $this->cli->shell(sprintf('cat %s', $path))->stdout;
		$exported = json_decode(trim($contents), true);

		$this->assertIsArray($exported, 'The exported file should be valid JSON. Got: ' . $contents);
		$this->assertSame('Full site backup', $exported['description']);
	}

	/**
	 * PRODUCT BUG. profileimport cannot work against a real site, and fails two different ways.
	 *
	 * Verified by hand against a Joomla 6.1 site running Akeeba Backup Professional 10.4.1, not just against this
	 * fixture:
	 *
	 * 1. With the default GET verb, the whole profile — configuration and filters, several kilobytes of JSON — is sent
	 *    as query string parameters. The URL comes out around 9.4KB and the server rejects it, which Remote CLI
	 *    reports as "Error #23 - Invalid JSON data returned from the server". importConfiguration is the one API
	 *    method whose payload cannot fit in a URL, so it has to be sent as POST.
	 *
	 * 2. Forced to POST with --verb=POST, the import *succeeds on the server* and then Remote CLI dies with an
	 *    uncaught TypeError: ImportConfiguration::__invoke() declares a return type of array, and the server answers
	 *    `true`. The user sees a fatal error and a stack trace after their profile has already been imported.
	 *
	 * Both live in akeeba/json-backup-api rather than here. The fixes are to force the verb to POST for this method,
	 * and to widen or drop that return type. Unskip once a release carrying them is pulled in.
	 */
	#[TestDox('An exported profile can be imported back')]
	public function testExportedProfileCanBeImported(): void
	{
		$this->markTestSkipped(
			'Known bug in akeeba/json-backup-api: profileimport sends its payload in the query string, and '
			. 'ImportConfiguration::__invoke() declares a return type the real server never satisfies. '
			. 'See this test\'s docblock.'
		);

		/** @noinspection PhpUnreachableStatementInspection */
		$exported = $this->runWithToken(['profileexport', '--id=2', '-m', '--quiet', '--'])->stdout;

		$this->assertSucceeded($this->runWithToken(['profileimport', '--data=' . trim($exported)]));

		$profiles = $this->runWithToken(['profiles', '-m', '--quiet']);

		/**
		 * Round-tripping is the assertion worth making: an export nothing can import is not an export. The imported
		 * copy shows up alongside the original rather than replacing it.
		 */
		$this->assertSame(
			2,
			substr_count($profiles->output(), 'Nightly database only'),
			'The imported profile should appear alongside the original.' . "\n" . $profiles->describe()
		);
	}

	#[TestDox('Exporting a profile which does not exist is an error')]
	public function testExportMissingProfile(): void
	{
		$result = $this->runWithToken(['profileexport', '--id=9999']);

		$this->assertNotSame(0, $result->exitCode, $result->describe());
	}
}
