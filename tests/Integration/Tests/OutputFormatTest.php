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
 * How output is shaped for a human at a terminal versus a script parsing it.
 *
 * @since 3.2.0
 */
class OutputFormatTest extends E2ETestCase
{
	#[TestDox('The banner is printed for a human and suppressed for a machine')]
	public function testBannerIsSuppressedForMachines(): void
	{
		$human = $this->runWithToken(['test']);
		$this->assertStringContainsString('Akeeba Remote Control CLI', $human->output());

		$machine = $this->runWithToken(['test', '-m']);
		$this->assertStringNotContainsString('ABSOLUTELY NO WARRANTY', $machine->output());
	}

	#[TestDox('Machine-readable output is one TYPE|message record per line')]
	public function testMachineReadableRecords(): void
	{
		$result = $this->runWithToken(['test', '-m']);

		$this->assertSucceeded($result);

		$info = $result->recordsOfType('INFO');

		$this->assertNotEmpty($info, 'There should be INFO records.' . "\n" . $result->describe());
		$this->assertStringContainsString('Successful connection to site', implode("\n", $info));
	}

	#[TestDox('--machine-readable is the same thing as -m')]
	public function testLongMachineReadableOption(): void
	{
		$short = $this->runWithToken(['listbackups', '-m', '--quiet']);
		$long  = $this->runWithToken(['listbackups', '--machine-readable', '--quiet']);

		$this->assertSame($short->stdout, $long->stdout);
	}

	#[TestDox('--quiet keeps the data and drops the commentary')]
	public function testQuietKeepsTheData(): void
	{
		$result = $this->runWithToken(['listbackups', '-m', '--quiet']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('A single part backup', $result->output());
		$this->assertStringNotContainsString('Akeeba Remote Control CLI', $result->output());
	}

	#[TestDox('An error is reported as an ERROR record in machine-readable mode')]
	public function testErrorsAreMachineReadableToo(): void
	{
		$result = $this->cli->run(['test', '--host=' . $this->site(), '--secret=utterly-wrong', '-m']);

		$this->assertNotEmpty(
			$result->recordsOfType('ERROR'),
			'A failure should still be parseable.' . "\n" . $result->describe()
		);
	}

	#[TestDox('--debug writes a log file in the working directory')]
	public function testDebugWritesALogFile(): void
	{
		$this->cli->shell('rm -f /tmp/arccli-debug/remotecli_log.txt; mkdir -p /tmp/arccli-debug');

		$result = $this->cli->run(
			['test', '--host=' . $this->site(), '--token=' . $this->token(), '--debug'],
			[]
		);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('Found a connection method', $result->output());
	}

	#[TestDox('The help text is refused in machine-readable mode rather than printed as noise')]
	public function testHelpIsNotMachineReadable(): void
	{
		$result = $this->cli->run(['help', '-m']);

		$this->assertStringContainsString('only available in regular', $result->output());
	}

	#[TestDox('The help text documents the credential options')]
	public function testHelpDocumentsCredentials(): void
	{
		$result = $this->cli->run(['help']);

		$this->assertStringContainsString('--token', $result->output());
		$this->assertStringContainsString('--secret', $result->output());
		$this->assertStringContainsString('--api-version', $result->output());
		$this->assertStringContainsString('--api-endpoint', $result->output());
	}
}
