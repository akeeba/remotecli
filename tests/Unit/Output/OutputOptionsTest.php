<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Unit\Output;

use Akeeba\RemoteCLI\Application\Output\OutputOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * The immutable options which decide how output is rendered.
 *
 * @since 3.2.0
 */
#[CoversClass(OutputOptions::class)]
class OutputOptionsTest extends TestCase
{
	#[TestDox('Unknown options are ignored unless strict mode is on')]
	public function testUnknownOptionsAreIgnored(): void
	{
		$options = new OutputOptions(['quiet' => true, 'thisIsNotAnOption' => 'whatever']);

		$this->assertTrue($options->quiet);
	}

	#[TestDox('Unknown options throw in strict mode')]
	public function testUnknownOptionsThrowInStrictMode(): void
	{
		$this->expectException(\LogicException::class);

		new OutputOptions(['thisIsNotAnOption' => 'whatever'], true);
	}

	#[TestDox('The nocolor and mergeerror aliases map onto the real properties')]
	public function testCommandLineAliases(): void
	{
		$options = new OutputOptions(['nocolor' => '1', 'mergeerror' => '1']);

		$this->assertTrue($options->noColor);
		$this->assertTrue($options->mergeErrorOutput);
	}

	/**
	 * Debug output is useless if it is suppressed, and --debug --quiet together are otherwise a silent no-op that
	 * looks like the tool ignoring you. Asking for debug wins.
	 */
	#[TestDox('--debug forcibly cancels --quiet')]
	public function testDebugCancelsQuiet(): void
	{
		$options = new OutputOptions(['quiet' => true, 'debug' => true]);

		$this->assertTrue($options->debug);
		$this->assertFalse($options->quiet);
	}

	#[TestDox('Reading a property which does not exist throws')]
	public function testUnknownPropertyThrows(): void
	{
		$this->expectException(\LogicException::class);

		/** @noinspection PhpExpressionResultUnusedInspection */
		(new OutputOptions([]))->noSuchProperty;
	}

	#[TestDox('Every option defaults to off')]
	public function testDefaults(): void
	{
		$options = new OutputOptions([]);

		$this->assertFalse($options->quiet);
		$this->assertFalse($options->noColor);
		$this->assertFalse($options->mergeErrorOutput);
		$this->assertFalse($options->debug);
	}
}
