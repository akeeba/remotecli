<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Unit\Utility;

use Akeeba\RemoteCLI\Application\Utility\IniParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Parsing of the ~/.akeebaremotecli configuration file.
 *
 * @since 3.2.0
 */
#[CoversClass(IniParser::class)]
class IniParserTest extends TestCase
{
	private string $file;

	protected function setUp(): void
	{
		parent::setUp();

		$this->file = tempnam(sys_get_temp_dir(), 'arccli-ini-');
	}

	protected function tearDown(): void
	{
		@unlink($this->file);

		parent::tearDown();
	}

	private function parse(string $contents, bool $sections = true): array
	{
		file_put_contents($this->file, $contents);

		return IniParser::parse_ini_file($this->file, $sections);
	}

	#[TestDox('Each section becomes its own array of options')]
	public function testSectionsArePreserved(): void
	{
		$parsed = $this->parse(
			<<<'INI'
			[akeebaremotecli]
			host=https://www.example.com
			secret=0123456789abcdef

			[nightly]
			host=https://other.example.com
			action=backup
			profile=2
			INI
		);

		$this->assertSame(['akeebaremotecli', 'nightly'], array_keys($parsed));
		$this->assertSame('https://www.example.com', $parsed['akeebaremotecli']['host']);
		$this->assertSame('backup', $parsed['nightly']['action']);
		$this->assertSame('2', $parsed['nightly']['profile']);
	}

	/**
	 * A Joomla API Token is Base64 and ends in one or two equals signs, which is exactly the character an INI file
	 * uses to separate a key from its value. The manual tells users to quote it; this is what that buys them.
	 */
	#[TestDox('A quoted value keeps the equals signs a Joomla API Token ends in')]
	public function testQuotedTokenSurvives(): void
	{
		$token  = 'c2hhMjU2OjQyOkVYQU1QTEUtVE9LRU4tTk9ULUEtUkVBTC1DUkVERU5USUFMUw==';
		$parsed = $this->parse("[akeebaremotecli]\nhost=https://www.example.com\ntoken=\"{$token}\"\n");

		$this->assertSame($token, $parsed['akeebaremotecli']['token']);
	}

	#[TestDox('A comment line is ignored')]
	public function testCommentsAreIgnored(): void
	{
		$parsed = $this->parse("[main]\n; this is a comment\nhost=https://www.example.com\n");

		$this->assertSame(['host' => 'https://www.example.com'], $parsed['main']);
	}

	#[TestDox('A file with no sections at all parses into a flat array')]
	public function testFlatFile(): void
	{
		$parsed = $this->parse("host=https://www.example.com\nsecret=abcdef\n", false);

		$this->assertSame('https://www.example.com', $parsed['host']);
		$this->assertSame('abcdef', $parsed['secret']);
	}

	#[TestDox('An empty file parses into an empty array rather than failing')]
	public function testEmptyFile(): void
	{
		$this->assertSame([], $this->parse(''));
	}
}
