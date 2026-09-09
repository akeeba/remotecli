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
 * Finding a way to talk to a site, and settling on the best one available.
 *
 * @since 3.2.0
 */
class ConnectionTest extends E2ETestCase
{
	#[TestDox('The newest API version the site speaks is the one used')]
	public function testPrefersTheNewestApiVersion(): void
	{
		$result = $this->runWithToken(['test']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('Successful connection to site', $result->output());
		$this->assertStringContainsString('Using the JSON API v3', $result->output());
	}

	#[TestDox('A site which only speaks the v2 API is reached over the v2 API')]
	public function testFallsBackToApiV2(): void
	{
		$this->fixture->speakOnly([1, 2]);

		$result = $this->runWithSecret(['test']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('Using the JSON API v2', $result->output());
	}

	#[TestDox('A site which only speaks the v1 API is reached over the v1 API')]
	public function testFallsBackToApiV1(): void
	{
		$this->fixture->speakOnly([1]);

		$result = $this->runWithSecret(['test']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('Using the JSON API v1', $result->output());
	}

	#[TestDox('Autodetection tries the API versions newest first')]
	public function testVersionsAreTriedNewestFirst(): void
	{
		$this->fixture->speakOnly([1, 2]);

		$this->assertSucceeded($this->runWithSecret(['test']));

		/**
		 * The v3 requests are refused with a 404 by a fixture which does not speak it, so they never reach the
		 * recorder. What did reach it proves v2 was tried before v1 — that is, that a site which speaks both is not
		 * talked to over the older one.
		 */
		$tried = $this->fixture->apiVersionsTried();

		$this->assertNotEmpty($tried);
		$this->assertSame(2, $tried[0], 'The v2 API should have been reached before the v1 API.');
	}

	#[TestDox('--api-version pins the version instead of negotiating it')]
	public function testApiVersionCanBePinned(): void
	{
		$result = $this->runWithSecret(['test', '--api-version=2']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('Using the JSON API v2', $result->output());
		$this->assertSame([2], $this->fixture->apiVersionsTried(), 'No other version should have been tried.');
	}

	#[TestDox('A site which speaks no version we know is reported as such')]
	public function testNoWayToConnect(): void
	{
		$this->fixture->speakOnly([]);

		$result = $this->runWithSecret(['test']);

		// Error #36: we cannot find a way to connect to your server.
		$this->assertFailedWithCode($result, 36);
	}

	#[TestDox('An Akeeba Solo endpoint is never asked for the v3 API')]
	public function testSoloIsNotAskedForApiV3(): void
	{
		/**
		 * The v3 API is a route in Joomla's API application. Akeeba Solo has no such thing, and it is identified by
		 * the endpoint it was configured with, so asking it is a wasted round trip on every single command.
		 */
		$result = $this->cli->run(
			['test', '--host=' . self::$configuration['solo'], '--secret=' . $this->secret()]
		);

		$this->assertSucceeded($result);
		$this->assertNotContains(3, $this->fixture->apiVersionsTried());
	}

	#[TestDox('The connection report names the API version and the credential actually used')]
	public function testConnectionReportIsAccurate(): void
	{
		$withToken = $this->runWithToken(['test']);
		$this->assertStringContainsString('authenticating with a Joomla API Token', $withToken->output());

		$this->fixture->reset();

		$withSecret = $this->runWithSecret(['test']);
		$this->assertStringContainsString('authenticating with the Secret Word', $withSecret->output());
	}

	#[TestDox('The reported edition and API level come from the site')]
	public function testVersionInformationIsReported(): void
	{
		$result = $this->runWithToken(['test']);

		$this->assertStringContainsString('Professional', $result->output());
		$this->assertStringContainsString('API level 600', $result->output());
	}

	#[TestDox('A response buried under a PHP notice is still understood')]
	public function testLeadingJunkIsTolerated(): void
	{
		/**
		 * Real sites do this to themselves, most often WordPress ones with display_errors left on. The client has to
		 * dig its JSON back out rather than reporting the site as broken.
		 */
		$this->fixture->arm('junk', 'before', 10);

		$result = $this->runWithToken(['test']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('Successful connection to site', $result->output());
	}

	#[TestDox('A response wrapped in triple hash markers is still understood')]
	public function testHashWrappedResponseIsTolerated(): void
	{
		$this->fixture->arm('junk', 'hashes', 10);

		$result = $this->runWithToken(['test']);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('Successful connection to site', $result->output());
	}

	/**
	 * KNOWN BUG in akeeba/json-backup-api, not in Remote CLI and not a test-writing mistake.
	 *
	 * Junk which *follows* the JSON — a notice raised during shutdown, a caching plugin's HTML comment — is not
	 * stripped, because AbstractHttpClient::removeResponseJunk() passes substr() an absolute offset where it wants a
	 * length. Remote CLI reports error #36, having decided the site speaks no version of the API at all.
	 *
	 * The library's own test suite records the same bug and names the fix:
	 * `substr($raw, $openBrace, $closeBrace - $openBrace + 1)`. Unskip this once a release carrying that fix is
	 * pulled in; nothing in Remote CLI has to change for it to start passing.
	 */
	#[TestDox('A response followed by junk is still understood')]
	public function testTrailingJunkIsTolerated(): void
	{
		$this->markTestSkipped(
			'Known bug in akeeba/json-backup-api: removeResponseJunk() cannot strip junk which follows the JSON.'
		);

		/** @noinspection PhpUnreachableStatementInspection */
		$this->fixture->arm('junk', 'after', 10);

		$this->assertSucceeded($this->runWithToken(['test']));
	}
}
