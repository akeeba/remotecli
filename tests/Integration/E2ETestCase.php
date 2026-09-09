<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Integration;

use Akeeba\RemoteCLI\Tests\Integration\Engine\CliResult;
use Akeeba\RemoteCLI\Tests\Integration\Engine\FixtureControl;
use Akeeba\RemoteCLI\Tests\Integration\Engine\RemoteCli;
use PHPUnit\Framework\TestCase;

/**
 * Shared plumbing for the integration suite.
 *
 * @since 3.2.0
 */
abstract class E2ETestCase extends TestCase
{
	protected static array $configuration;

	protected RemoteCli $cli;

	protected FixtureControl $fixture;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		self::$configuration = $GLOBALS['ARCCLI_E2E_CONFIG'];
	}

	protected function setUp(): void
	{
		parent::setUp();

		$this->cli     = new RemoteCli(self::$configuration['compose'], self::$configuration['service']);
		$this->fixture = new FixtureControl(self::$configuration['control']);

		// Every test starts from the same server. Nothing a previous test did can reach this one.
		$this->fixture->reset();
	}

	protected function site(): string
	{
		return self::$configuration['site'];
	}

	protected function token(): string
	{
		return self::$configuration['token'];
	}

	protected function secret(): string
	{
		return self::$configuration['secret'];
	}

	protected function restrictedToken(): string
	{
		return self::$configuration['restrictedToken'];
	}

	/**
	 * Runs a command against the fixture site, authenticating with a Joomla API Token.
	 *
	 * @param   string[]  $arguments  The command and any options beyond the host and the credential
	 */
	protected function runWithToken(array $arguments, array $env = []): CliResult
	{
		return $this->cli->run(
			array_merge(['--host=' . $this->site(), '--token=' . $this->token()], $arguments),
			$env
		);
	}

	/**
	 * Runs a command against the fixture site, authenticating with the Secret Word.
	 */
	protected function runWithSecret(array $arguments, array $env = []): CliResult
	{
		return $this->cli->run(
			array_merge(['--host=' . $this->site(), '--secret=' . $this->secret()], $arguments),
			$env
		);
	}

	/**
	 * Asserts the run succeeded, showing everything it printed if it did not.
	 */
	protected function assertSucceeded(CliResult $result): void
	{
		$this->assertSame(0, $result->exitCode, 'The command was expected to succeed.' . "\n" . $result->describe());
	}

	/**
	 * Asserts the run failed with this Remote CLI error code.
	 *
	 * The exit code is the error's own code, which is what a shell script branches on, so it is asserted rather than
	 * merely "non-zero". The message is checked too, because an exit code with the wrong explanation attached is a
	 * user sent off debugging the wrong thing.
	 */
	protected function assertFailedWithCode(CliResult $result, int $code, ?string $messageContains = null): void
	{
		$this->assertSame(
			$code,
			$result->exitCode,
			sprintf('The command was expected to fail with code %d.', $code) . "\n" . $result->describe()
		);

		$this->assertStringContainsString(
			sprintf('Error #%d', $code),
			$result->output(),
			'The error message should name the error code.' . "\n" . $result->describe()
		);

		if ($messageContains !== null)
		{
			$this->assertStringContainsString($messageContains, $result->output(), $result->describe());
		}
	}
}
