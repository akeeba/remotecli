<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Unit\Command;

use Akeeba\BackupJsonApi\Exception\NoConfiguredHost;
use Akeeba\BackupJsonApi\Exception\NoConfiguredSecret;
use Akeeba\RemoteCLI\Application\Output\Output;
use Akeeba\RemoteCLI\Application\Output\OutputOptions;
use Akeeba\RemoteCLI\Tests\Unit\Stub\SpyCommand;
use Akeeba\RemoteCLI\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\NullLogger;

/**
 * Which combinations of credentials are enough to run a command, and what reaches the API options.
 *
 * @since 3.2.0
 */
#[CoversClass(\Akeeba\RemoteCLI\Application\Command\AbstractCommand::class)]
class CredentialsTest extends UnitTestCase
{
	private const TOKEN = 'c2hhMjU2OjQyOkVYQU1QTEUtVE9LRU4tTk9ULUEtUkVBTC1DUkVERU5USUFMUw==';

	private const SECRET = 'TheAkeebaBackupSecretWord';

	private function makeCommand(array $arguments): SpyCommand
	{
		$command = new SpyCommand(
			$this->makeInput($arguments),
			new Output(new OutputOptions(['quiet' => true]), 'machine'),
			new NullLogger()
		);

		$command->prepare();

		return $command;
	}

	#[TestDox('A Joomla API Token on its own is enough to run a command')]
	public function testTokenAloneIsEnough(): void
	{
		$command = $this->makeCommand(['--host=https://www.example.com', '--token=' . self::TOKEN]);

		$command->callAssertConfigured();

		$this->assertSame(self::TOKEN, $command->readOption('token'));
	}

	#[TestDox('A Secret Word on its own is enough to run a command')]
	public function testSecretAloneIsEnough(): void
	{
		$command = $this->makeCommand(['--host=https://www.example.com', '--secret=' . self::SECRET]);

		$command->callAssertConfigured();

		$this->assertSame(self::SECRET, $command->readOption('secret'));
	}

	#[TestDox('Both credentials together are accepted, and both reach the API options')]
	public function testBothCredentialsAreAccepted(): void
	{
		$command = $this->makeCommand(
			['--host=https://www.example.com', '--token=' . self::TOKEN, '--secret=' . self::SECRET]
		);

		$command->callAssertConfigured();

		$options = $command->callGetApiOptions();

		/**
		 * Both have to survive into the options. Which one is actually presented to the site is the library's
		 * decision, made per candidate during autodetection, and it can only prefer the token if it has been given
		 * both to choose from.
		 */
		$this->assertSame(self::TOKEN, $options->token);
		$this->assertSame(self::SECRET, $options->secret);
	}

	#[TestDox('Neither credential is refused, and the refusal names both of them')]
	public function testNoCredentialIsRefused(): void
	{
		$command = $this->makeCommand(['--host=https://www.example.com']);

		$this->expectException(NoConfiguredSecret::class);

		$command->callAssertConfigured();
	}

	#[TestDox('A missing host is refused even when a credential was given')]
	public function testNoHostIsRefused(): void
	{
		$command = $this->makeCommand(['--token=' . self::TOKEN]);

		$this->expectException(NoConfiguredHost::class);

		$command->callAssertConfigured();
	}

	/**
	 * A credential must reach the API options exactly as it was typed.
	 *
	 * Nothing in the application filters it today, so this locks that in rather than fixing it. It is worth locking
	 * in: a Joomla API Token is Base64 and ends in one or two equals signs, and the obvious "tidy up the input"
	 * change — running it through the `cmd` filter, as the emptiness check next door once did — would eat exactly
	 * those characters and produce an authentication failure with no visible cause.
	 */
	#[TestDox('Credential $description survives being read')]
	#[DataProvider('provideAwkwardCredentials')]
	public function testCredentialsAreNotMangled(string $description, string $credential): void
	{
		$command = $this->makeCommand(['--host=https://www.example.com', '--token=' . $credential]);

		$command->callAssertConfigured();

		$this->assertSame($credential, $command->readOption('token'), $description);
		$this->assertSame($credential, $command->callGetApiOptions()->token, $description);
	}

	public static function provideAwkwardCredentials(): array
	{
		return [
			'base64 with one equals sign'  => ['ending in one equals sign', 'c2hhMjU2OjcwOmFiYw='],
			'base64 with two equals signs' => ['ending in two equals signs', 'c2hhMjU2OjcwOmFiY2Q=='],
			'base64 with a plus and slash' => ['containing + and /', 'YWJj+ZGVm/Z2hp'],
			'a colon separated token'      => ['containing colons', 'sha256:70:8031774bab5a640f'],
			'a plain secret word'          => ['a plain alphanumeric Secret Word', self::SECRET],
		];
	}

	#[TestDox('An option given with no value at all does not count as a credential')]
	public function testValuelessOptionIsNotACredential(): void
	{
		/**
		 * The command line parser turns a bare `--token` into boolean true. That is a user who meant to supply a
		 * credential and did not, so it has to be refused as loudly as leaving the option out altogether — anything
		 * else sends them off debugging an authentication failure instead of a typo.
		 */
		$command = $this->makeCommand(['--host=https://www.example.com', '--token']);

		$this->expectException(NoConfiguredSecret::class);

		$command->callAssertConfigured();
	}

	#[TestDox('Whitespace around a credential does not make it look present')]
	public function testWhitespaceOnlyCredentialIsRefused(): void
	{
		$command = $this->makeCommand(['--host=https://www.example.com', '--token=   ']);

		$this->expectException(NoConfiguredSecret::class);

		$command->callAssertConfigured();
	}
}
