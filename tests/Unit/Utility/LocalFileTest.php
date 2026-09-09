<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Unit\Utility;

use Akeeba\RemoteCLI\Application\Utility\LocalFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Reading configuration sections out of the local configuration file.
 *
 * @since 3.2.0
 */
#[CoversClass(LocalFile::class)]
class LocalFileTest extends TestCase
{
	private string $file;

	protected function setUp(): void
	{
		parent::setUp();

		$this->file = tempnam(sys_get_temp_dir(), 'arccli-cfg-');

		file_put_contents(
			$this->file,
			<<<'INI'
			host=https://default.example.com
			secret=TheDefaultSecret

			[nightly]
			host=https://nightly.example.com
			token="c2hhMjU2OjcwOmFiYw="
			action=backup
			profile=2
			INI
		);
	}

	protected function tearDown(): void
	{
		@unlink($this->file);

		parent::tearDown();
	}

	#[TestDox('Options outside any section land in the default section')]
	public function testOptionsOutsideASectionGoToTheDefaultSection(): void
	{
		$configuration = (new LocalFile($this->file))->getConfiguration();

		$this->assertSame('https://default.example.com', $configuration['host']);
		$this->assertSame('TheDefaultSecret', $configuration['secret']);
	}

	#[TestDox('A named section is returned whole')]
	public function testNamedSection(): void
	{
		$configuration = (new LocalFile($this->file))->getConfiguration('nightly');

		$this->assertSame('https://nightly.example.com', $configuration['host']);
		$this->assertSame('c2hhMjU2OjcwOmFiYw=', $configuration['token']);
		$this->assertSame('backup', $configuration['action']);
	}

	#[TestDox('An unknown section is an empty configuration, not an error')]
	public function testUnknownSection(): void
	{
		$this->assertSame([], (new LocalFile($this->file))->getConfiguration('nosuchsection'));
	}

	#[TestDox('A missing configuration file is an empty configuration, not an error')]
	public function testMissingFile(): void
	{
		$missing = sys_get_temp_dir() . '/arccli-does-not-exist-' . bin2hex(random_bytes(6));

		$this->assertSame([], (new LocalFile($missing))->getConfiguration());
		$this->assertSame([], (new LocalFile($missing))->getConfiguration('nightly'));
	}

	#[TestDox('The default file path is inside the home directory')]
	public function testDefaultFilePath(): void
	{
		$path = (new LocalFile($this->file))->getDefaultFilepath();

		$this->assertStringEndsWith('/' . LocalFile::defaultFileName, $path);
	}
}
