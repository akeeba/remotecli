<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Integration\Tests;

use Akeeba\RemoteCLI\Tests\Integration\E2ETestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * The shapes of URL a user may reasonably paste into --host, over real HTTP against a real web server.
 *
 * The unit suite checks what the host option is rewritten to. This checks that the rewritten value actually reaches
 * Joomla's API application, which is a question only a web server with real URL routing can answer.
 *
 * @since 3.2.0
 */
class HostNormalisationTest extends E2ETestCase
{
	#[TestDox('Connecting works when the host is $_dataName')]
	#[DataProvider('provideHostShapes')]
	public function testHostShapesAllConnect(string $suffix): void
	{
		$result = $this->cli->run(
			['test', '--host=' . $this->site() . $suffix, '--token=' . $this->token()]
		);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('Using the JSON API v3', $result->output());
	}

	public static function provideHostShapes(): array
	{
		return [
			'the site root'                            => [''],
			'the site root with a trailing slash'      => ['/'],
			'the API application URL'                  => ['/api/index.php'],
			'the rewritten API application URL'        => ['/api'],
			'the API application URL, trailing slash'  => ['/api/index.php/'],
		];
	}

	#[TestDox('The rewritten /api form really is served by URL rewriting')]
	public function testRewrittenFormIsReachable(): void
	{
		/**
		 * The two forms are not interchangeable on every server: /api works only where the API application's URL
		 * rewriting is in effect, which is why Remote CLI tries the unrewritten /api/index.php first. This proves the
		 * fixture actually offers both, so the case above is testing something real rather than the same path twice.
		 */
		$result = $this->cli->run(
			['test', '--host=' . $this->site(), '--token=' . $this->token(), '--api-endpoint=api']
		);

		$this->assertSucceeded($result);

		$uris = array_map(fn(array $request) => $request['uri'], $this->fixture->requests());

		$this->assertNotEmpty(
			array_filter($uris, fn(string $uri) => str_starts_with($uri, '/api/v3/')),
			'The request should have gone to the rewritten /api/v3/... route. Saw: ' . implode(', ', $uris)
		);
	}

	#[TestDox('An explicit --api-endpoint keeps the host exactly as it was typed')]
	public function testExplicitEndpointKeepsTheHost(): void
	{
		/**
		 * This is the escape hatch for a site installed in a directory called `api`, where the site's root and the API
		 * application's URL look identical. Here it is exercised the other way round — the host is already the site
		 * root — so it proves only that naming the endpoint stops the host being touched.
		 */
		$result = $this->cli->run(
			['test', '--host=' . $this->site(), '--token=' . $this->token(), '--api-endpoint=api/index.php']
		);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('Using the JSON API v3', $result->output());
	}

	#[TestDox('The split is reported when --debug is on')]
	public function testTheSplitIsVisibleInDebugOutput(): void
	{
		$result = $this->cli->run(
			['test', '--host=' . $this->site() . '/api/index.php', '--token=' . $this->token(), '--debug']
		);

		$this->assertSucceeded($result);
		$this->assertStringContainsString('points at Joomla\'s API application', $result->output());
	}

	#[TestDox('A host which is not a site at all is reported as an error')]
	public function testUnreachableHostIsReported(): void
	{
		$result = $this->cli->run(
			['test', '--host=http://nothing.example.test', '--token=' . $this->token()]
		);

		$this->assertStringContainsString('Could not resolve host', $result->output());
		$this->assertSame([], $this->fixture->requests(), 'Nothing should have reached the fixture.');
	}

	/**
	 * PRODUCT BUG, not a test-writing mistake. A network failure exits 0, which the shell reads as success.
	 *
	 * remote.php ends with `exit($e->getCode())`. A cURL-level failure — an unresolvable host, a refused connection, a
	 * TLS error — surfaces as an exception whose code is 0, so the process reports success while printing
	 * "Error #0 - Could not resolve host". Verified by hand as well as here.
	 *
	 * That matters more here than it would almost anywhere else: this tool exists to be run unattended from cron. A
	 * backup job whose site has fallen off DNS exits 0 every night, and nobody finds out until they need the backup.
	 *
	 * The fix is `exit($e->getCode() ?: 255)`, matching the 255 the PHP version guard already uses. It is a behaviour
	 * change rather than a test fix, so this stays skipped until it is made deliberately.
	 */
	#[TestDox('A network failure exits non-zero so a cron job notices')]
	public function testUnreachableHostExitsNonZero(): void
	{
		$this->markTestSkipped(
			'Known bug: remote.php exits with the exception code, and a network error carries code 0, '
			. 'so a failed run reports success to the shell. See this test\'s docblock.'
		);

		/** @noinspection PhpUnreachableStatementInspection */
		$result = $this->cli->run(
			['test', '--host=http://nothing.example.test', '--token=' . $this->token()]
		);

		$this->assertNotSame(0, $result->exitCode, $result->describe());
	}
}
