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
 * The ~/.akeebaremotecli file, which is how anything unattended is actually configured.
 *
 * These run in the container, which has a home directory of its own. Nothing here can touch the real configuration
 * file on the machine running the suite.
 *
 * @since 3.2.0
 */
class ConfigurationFileTest extends E2ETestCase
{
	private const CONFIG_PATH = '/home/tester/.akeebaremotecli';

	protected function setUp(): void
	{
		parent::setUp();

		$this->cli->shell(sprintf('rm -f %s', self::CONFIG_PATH));
	}

	#[TestDox('The default section supplies the host and the credential')]
	public function testDefaultSection(): void
	{
		$this->cli->writeFile(
			self::CONFIG_PATH,
			sprintf("host=%s\ntoken=\"%s\"\n", $this->site(), $this->token())
		);

		$result = $this->cli->run(['test']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('Successful connection to site', $result->output());
	}

	#[TestDox('A named section is selected with --host')]
	public function testNamedSection(): void
	{
		$this->cli->writeFile(
			self::CONFIG_PATH,
			sprintf(
				"[nightly]\nhost=%s\ntoken=\"%s\"\naction=backup\nprofile=2\n",
				$this->site(),
				$this->token()
			)
		);

		$result = $this->cli->run(['--host=nightly']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('The backup finished successfully.', $result->output());
	}

	#[TestDox('A quoted token keeps the equals signs it ends in')]
	public function testQuotedTokenSurvivesTheIniParser(): void
	{
		/**
		 * A Joomla API Token is Base64 and ends in one or two equals signs, which is what an INI file uses to separate
		 * a key from its value. The manual tells users to quote it; this is the end-to-end proof that quoting works.
		 */
		$paddedToken = $this->token() . '==';

		$this->cli->writeFile(
			self::CONFIG_PATH,
			sprintf("host=%s\ntoken=\"%s\"\n", $this->site(), $paddedToken)
		);

		// The padded token is not the fixture's, so it is refused — but it has to be refused *whole*.
		$this->cli->run(['test']);

		[$type, $value] = $this->fixture->lastCredential();

		$this->assertSame('token', $type);
		$this->assertSame($paddedToken, $value, 'The token must reach the site with its padding intact.');
	}

	#[TestDox('An unknown section leaves the tool with nothing to work with')]
	public function testUnknownSection(): void
	{
		$this->cli->writeFile(self::CONFIG_PATH, "[nightly]\nhost=https://www.example.com\n");

		$result = $this->cli->run(['test', '--host=nosuchsection']);

		// Error #37: no credential. The section supplied nothing, so nothing was configured.
		$this->assertFailedWithCode($result, 37);
	}

	#[TestDox('No configuration file at all is not an error in itself')]
	public function testNoConfigurationFile(): void
	{
		$result = $this->runWithToken(['test']);

		$this->assertSucceeded($result);
	}
}
