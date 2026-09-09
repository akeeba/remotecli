<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Unit;

use Akeeba\RemoteCLI\Application\Input\Cli;
use PHPUnit\Framework\TestCase;

/**
 * Shared plumbing for the unit suite.
 *
 * @since 3.2.0
 */
abstract class UnitTestCase extends TestCase
{
	/**
	 * The real $argv, put back after every test.
	 *
	 * @var   array|null
	 * @since 3.2.0
	 */
	private ?array $originalArgv = null;

	protected function setUp(): void
	{
		parent::setUp();

		$this->originalArgv = $GLOBALS['argv'] ?? null;
	}

	protected function tearDown(): void
	{
		if ($this->originalArgv === null)
		{
			unset($GLOBALS['argv']);
		}
		else
		{
			$GLOBALS['argv'] = $this->originalArgv;
		}

		parent::tearDown();
	}

	/**
	 * Builds a Cli input object out of a command line.
	 *
	 * Cli parses the global $argv rather than anything handed to its constructor, so the only honest way to test it is
	 * to put a command line there — which has the happy side effect of exercising the real parser instead of a mock
	 * of it. The executable name is prepended because Cli shifts it off before parsing.
	 *
	 * @param   string[]  $arguments  The command line, without the executable name
	 *
	 * @return  Cli
	 * @since   3.2.0
	 */
	protected function makeInput(array $arguments): Cli
	{
		$GLOBALS['argv'] = array_merge(['remote.php'], $arguments);

		return new Cli();
	}
}
