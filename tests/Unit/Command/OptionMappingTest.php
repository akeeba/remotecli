<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Unit\Command;

use Akeeba\RemoteCLI\Application\Output\Output;
use Akeeba\RemoteCLI\Application\Output\OutputOptions;
use Akeeba\RemoteCLI\Tests\Unit\Stub\SpyCommand;
use Akeeba\RemoteCLI\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\NullLogger;

/**
 * The short and kebab-case option spellings prepare() translates into the names the rest of the code uses.
 *
 * @since 3.2.0
 */
#[CoversClass(\Akeeba\RemoteCLI\Application\Command\AbstractCommand::class)]
class OptionMappingTest extends UnitTestCase
{
	private const TOKEN = 'c2hhMjU2OjcwOmFiYw=';

	private function prepareWith(array $arguments): SpyCommand
	{
		$command = new SpyCommand(
			$this->makeInput($arguments),
			new Output(new OutputOptions(['quiet' => true]), 'machine'),
			new NullLogger()
		);

		$command->prepare();

		return $command;
	}

	#[TestDox('-h, -s and -t map to the host, secret and token options')]
	public function testShortOptionsAreMapped(): void
	{
		$command = $this->prepareWith(
			['-h', 'https://www.example.com', '-s', 'TheSecretWord', '-t', self::TOKEN]
		);

		$this->assertSame('https://www.example.com', $command->readOption('host'));
		$this->assertSame('TheSecretWord', $command->readOption('secret'));
		$this->assertSame(self::TOKEN, $command->readOption('token'));

		$command->callAssertConfigured();
	}

	#[TestDox('A long option is not overwritten by an absent short one')]
	public function testLongOptionsSurviveWithoutTheirShortForms(): void
	{
		$command = $this->prepareWith(['--host=https://www.example.com', '--token=' . self::TOKEN]);

		$this->assertSame('https://www.example.com', $command->readOption('host'));
		$this->assertSame(self::TOKEN, $command->readOption('token'));
	}

	#[TestDox('--api-version and --api-endpoint map to the library\'s camelCase spellings')]
	public function testKebabCaseOptionsAreMapped(): void
	{
		$command = $this->prepareWith(
			[
				'--host=https://www.example.com',
				'--token=' . self::TOKEN,
				'--api-version=3',
				'--api-endpoint=custom/api.php',
			]
		);

		$this->assertSame('3', $command->readOption('apiVersion'));
		$this->assertSame('custom/api.php', $command->readOption('apiEndpoint'));

		$options = $command->callGetApiOptions();

		// The library takes the version as an integer, and normalises the endpoint by stripping surrounding slashes.
		$this->assertSame(3, $options->apiVersion);
		$this->assertSame('custom/api.php', $options->apiEndpoint);
	}

	#[TestDox('-m sets the machine-readable flag')]
	public function testMachineReadableShortOptionIsMapped(): void
	{
		$command = $this->prepareWith(['--host=https://www.example.com', '--token=' . self::TOKEN, '-m']);

		$this->assertTrue((bool) $command->readOption('machine-readable'));
	}

	#[TestDox('The --certificate option never reaches the API options')]
	public function testCertificateIsStrippedFromTheApiOptions(): void
	{
		/**
		 * remote.php has already appended the certificate to the temporary CA bundle by the time a command runs, and
		 * the library has no `certificate` option to receive it. Leaving it in would be harmless only because the
		 * library is not strict about unknown options; stripping it says what is actually meant.
		 */
		$command = $this->prepareWith(
			[
				'--host=https://www.example.com',
				'--token=' . self::TOKEN,
				'--certificate=/home/me/ca.pem',
			]
		);

		$options = $command->callGetApiOptions()->toArray();

		$this->assertArrayNotHasKey('certificate', $options);
		$this->assertSame(AKEEBA_CACERT_PEM, $options['capath']);
	}

	#[TestDox('An explicitly passed option beats the one on the command line')]
	public function testAdditionalOptionsWin(): void
	{
		$command = $this->prepareWith(
			['--host=https://www.example.com', '--token=' . self::TOKEN, '--verb=GET']
		);

		$this->assertSame('POST', $command->callGetApiOptions(['verb' => 'POST'])->verb);
	}
}
