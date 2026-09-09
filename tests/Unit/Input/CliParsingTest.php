<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Unit\Input;

use Akeeba\RemoteCLI\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * How a command line becomes options and arguments.
 *
 * @since 3.2.0
 */
#[CoversClass(\Akeeba\RemoteCLI\Application\Input\Cli::class)]
class CliParsingTest extends UnitTestCase
{
	#[TestDox('The command name is the first argument which is not an option')]
	public function testCommandIsTheFirstBareArgument(): void
	{
		$input = $this->makeInput(['backup', '--profile=2']);

		$this->assertSame(['backup'], $input->getArguments());
		$this->assertSame('remote.php', $input->getExecutable());
	}

	#[TestDox('--option=value keeps everything after the first equals sign')]
	public function testEqualsSignInAValueIsKept(): void
	{
		$input = $this->makeInput(['--token=abc==']);

		$this->assertSame('abc==', $input->get('token', null, 'raw'));
	}

	#[TestDox('--option value takes the next argument as the value')]
	public function testSpaceSeparatedValue(): void
	{
		$input = $this->makeInput(['--host', 'https://www.example.com', 'test']);

		$this->assertSame('https://www.example.com', $input->get('host', null, 'raw'));
		$this->assertSame(['test'], $input->getArguments());
	}

	#[TestDox('A long option with no value at all is boolean true')]
	public function testValuelessLongOptionIsTrue(): void
	{
		$input = $this->makeInput(['--debug', '--quiet']);

		$this->assertTrue($input->get('debug', null, 'raw'));
		$this->assertTrue($input->get('quiet', null, 'raw'));
	}

	#[TestDox('-abc sets three separate flags')]
	public function testClusteredShortOptions(): void
	{
		$input = $this->makeInput(['-mqd']);

		$this->assertTrue($input->get('m', null, 'raw'));
		$this->assertTrue($input->get('q', null, 'raw'));
		$this->assertTrue($input->get('d', null, 'raw'));
	}

	#[TestDox('-k=value assigns a value to a single short option')]
	public function testShortOptionWithEquals(): void
	{
		$input = $this->makeInput(['-s=TheSecretWord']);

		$this->assertSame('TheSecretWord', $input->get('s', null, 'raw'));
	}

	#[TestDox('A bare -- is preserved as an argument')]
	public function testDoubleDashIsAnArgument(): void
	{
		/**
		 * profileexport and profileimport use it to mean “standard output” and “standard input”, so it has to reach
		 * the argument list rather than being eaten as an option separator.
		 */
		$input = $this->makeInput(['profileexport', '--id=2', '--']);

		$this->assertSame(['profileexport', '--'], $input->getArguments());
	}

	#[TestDox('$_dataName is read back exactly as typed')]
	#[DataProvider('provideAwkwardValues')]
	public function testAwkwardValuesSurvive(string $value): void
	{
		$input = $this->makeInput(['--dlurl=' . $value]);

		$this->assertSame($value, $input->get('dlurl', null, 'raw'));
	}

	public static function provideAwkwardValues(): array
	{
		return [
			'an FTP URL with credentials' => ['ftp://user:p%40ss@ftp.example.com:21/public_html/backup'],
			'a Windows path'              => ['c:\\Downloads\\backups'],
			'a value containing spaces'   => ['Backup taken on Monday'],
			'a value containing quotes'   => ['He said "hello"'],
			'a value which looks numeric' => ['0012'],
		];
	}

	#[TestDox('Data merged from a configuration file is readable as an option')]
	public function testMergeData(): void
	{
		$input = $this->makeInput(['test']);

		$input->mergeData(['host' => 'https://www.example.com', 'secret' => 'TheSecretWord']);

		$this->assertSame('https://www.example.com', $input->get('host', null, 'raw'));
	}

	/**
	 * KNOWN BUG. The configuration file overrules the command line, which is backwards.
	 *
	 * remote.php builds the input from the command line and then calls mergeData() with the matching section of
	 * ~/.akeebaremotecli. mergeData() is `array_replace_recursive($this->data, $data)`, so the file's value replaces
	 * the one the user typed rather than filling in for a value they did not type.
	 *
	 * The manual documents the opposite, in the Configuration files section: given a section [example] carrying
	 * `profile=3`, it offers `remote.phar backup --host=example --profile=1` as the way to override an option, and
	 * says a backup with profile #1 is taken. It is not; profile #3 is used. Verified by hand as well as here.
	 *
	 * Swapping the two arguments of array_replace_recursive() fixes it. That is a behaviour change rather than a test
	 * fix, so this test stays skipped until it is made deliberately, rather than being rewritten to assert that the
	 * bug is the intended behaviour.
	 */
	#[TestDox('A command line option beats the same option from a configuration file')]
	public function testCommandLineBeatsMergedData(): void
	{
		$this->markTestSkipped(
			'Known bug: Input::mergeData() lets the configuration file overrule the command line. '
			. 'The manual documents the opposite. See this test\'s docblock.'
		);

		/** @noinspection PhpUnreachableStatementInspection */
		$input = $this->makeInput(['test', '--profile=7']);

		$input->mergeData(['profile' => '1']);

		$this->assertSame(7, $input->getInt('profile'));
	}

	#[TestDox('A configuration file supplies options the command line did not')]
	public function testMergedDataFillsInMissingOptions(): void
	{
		/**
		 * This is the half of the merge which does work, and the half every documented example actually relies on:
		 * the file carries the host and the credential, the command line carries the command.
		 */
		$input = $this->makeInput(['backup']);

		$input->mergeData(['host' => 'https://www.example.com', 'profile' => '2']);

		$this->assertSame(['backup'], $input->getArguments());
		$this->assertSame('https://www.example.com', $input->get('host', null, 'raw'));
		$this->assertSame(2, $input->getInt('profile'));
	}
}
