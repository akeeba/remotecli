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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Log\NullLogger;

/**
 * Splitting the URL of Joomla's API application out of the host option.
 *
 * The JSON API v3 lives at https://www.example.com/api/index.php, so that is the URL a user reaches for when asked
 * which endpoint to connect to. The host is meant to be the site's root, and handing us the API URL instead used to
 * send every request to .../api/api/index.php.
 *
 * @since 3.2.0
 */
#[CoversClass(\Akeeba\RemoteCLI\Application\Command\AbstractCommand::class)]
class HostNormalisationTest extends UnitTestCase
{
	private const TOKEN = 'c2hhMjU2OjcwOmFiYw=';

	private function prepareWith(array $arguments): SpyCommand
	{
		$command = new SpyCommand(
			$this->makeInput(array_merge(['--token=' . self::TOKEN], $arguments)),
			new Output(new OutputOptions(['quiet' => true]), 'machine'),
			new NullLogger()
		);

		$command->prepare();

		return $command;
	}

	#[TestDox('$_dataName is split into a site and an API endpoint')]
	#[DataProvider('provideApiApplicationUrls')]
	public function testApiApplicationUrlIsSplit(string $host, string $expectedHost, string $expectedEndpoint): void
	{
		$command = $this->prepareWith(['--host=' . $host]);

		$this->assertSame($expectedHost, $command->readOption('host'));
		$this->assertSame($expectedEndpoint, $command->readOption('apiEndpoint'));
	}

	public static function provideApiApplicationUrls(): array
	{
		return [
			'the plain API application URL'   => [
				'https://www.example.com/api/index.php', 'https://www.example.com', 'api/index.php',
			],
			'its rewritten form'              => [
				'https://www.example.com/api', 'https://www.example.com', 'api',
			],
			'with a trailing slash'           => [
				'https://www.example.com/api/index.php/', 'https://www.example.com', 'api/index.php',
			],
			'on a site in a subdirectory'     => [
				'https://www.example.com/mysite/api/index.php', 'https://www.example.com/mysite', 'api/index.php',
			],
			'on a non-standard port'          => [
				'https://www.example.com:8443/api/index.php', 'https://www.example.com:8443', 'api/index.php',
			],
			'with HTTP basic credentials'     => [
				'https://joe:sekrit@www.example.com/api/index.php', 'https://joe:sekrit@www.example.com',
				'api/index.php',
			],
		];
	}

	#[TestDox('$_dataName is left exactly as the user typed it')]
	#[DataProvider('provideHostsToLeaveAlone')]
	public function testHostIsLeftAlone(string $host): void
	{
		$command = $this->prepareWith(['--host=' . $host]);

		$this->assertSame($host, $command->readOption('host'));
		$this->assertNull($command->readOption('apiEndpoint'));
	}

	public static function provideHostsToLeaveAlone(): array
	{
		return [
			'a bare site root'                     => ['https://www.example.com'],
			'a site root with a trailing slash'    => ['https://www.example.com/'],
			'a site in a subdirectory'             => ['https://www.example.com/mysite'],
			'the frontend endpoint'                => ['https://www.example.com/index.php'],
			// The v1 and v2 Endpoint URLs. Their path is the site's root plus index.php, never the API application's.
			'a JSON API v2 Endpoint URL'           => [
				'https://www.example.com/index.php?option=com_akeebabackup&view=Api&format=raw',
			],
			'a JSON API v1 Endpoint URL'           => [
				'https://www.example.com/index.php?option=com_akeeba&view=json&format=raw',
			],
			// A site whose own root really is /api, reached through the Endpoint URL, which carries a query string.
			'an Endpoint URL below a path of /api' => [
				'https://www.example.com/api/index.php?option=com_akeebabackup&view=Api&format=raw',
			],
			'the Akeeba Solo endpoint'             => ['https://www.example.com/remote.php'],
			'the WordPress endpoint'               => ['https://www.example.com/wp-admin/admin-ajax.php'],
			// `api` has to be a whole path segment. A directory merely starting with those letters is not one.
			'a directory called apidocs'           => ['https://www.example.com/apidocs'],
			'a host which is merely called api'    => ['https://api.example.com'],
		];
	}

	/**
	 * The escape hatch for the one site the split gets wrong.
	 *
	 * A site installed in a directory called `api` has https://www.example.com/api as its root, not as its API
	 * application, and nothing in the URL says which of the two it is. Naming the API endpoint explicitly is how a
	 * user settles it, so an explicit --api-endpoint has to stop us touching the host at all.
	 */
	#[TestDox('An explicit --api-endpoint stops the host being rewritten')]
	public function testExplicitApiEndpointSuppressesTheSplit(): void
	{
		$command = $this->prepareWith(
			['--host=https://www.example.com/api', '--api-endpoint=api/index.php']
		);

		$this->assertSame('https://www.example.com/api', $command->readOption('host'));
		$this->assertSame('api/index.php', $command->readOption('apiEndpoint'));
	}

	#[TestDox('An explicit --api-endpoint is not overwritten by the split')]
	public function testExplicitApiEndpointWinsOverTheGuess(): void
	{
		$command = $this->prepareWith(
			['--host=https://www.example.com/api/index.php', '--api-endpoint=somewhere/else.php']
		);

		$this->assertSame('https://www.example.com/api/index.php', $command->readOption('host'));
		$this->assertSame('somewhere/else.php', $command->readOption('apiEndpoint'));
	}

	#[TestDox('A host with no scheme keeps its shape when it is split')]
	public function testSchemelessHostKeepsItsShape(): void
	{
		$command = $this->prepareWith(['--host=www.example.com/api/index.php']);

		$this->assertSame('www.example.com', $command->readOption('host'));
		$this->assertSame('api/index.php', $command->readOption('apiEndpoint'));
	}

	/**
	 * A malformed authority survives the split exactly as the user typed it.
	 *
	 * Rejecting a malformed host is the library's job, and it says so far better than this normalisation could. What
	 * this must not do is quietly hand the library a *different* malformed host: reassembling the URL from
	 * parse_url()'s output turned `http://:::` into `http://::`, so the error message named a URL the user had never
	 * typed. Splitting is still the right call here — the path really is the API application's — so the assertion is
	 * that the authority came through untouched, not that nothing happened.
	 */
	#[TestDox('A malformed authority is preserved rather than reassembled')]
	public function testMalformedAuthorityIsPreserved(): void
	{
		$command = $this->prepareWith(['--host=http://:::/api/index.php']);

		$this->assertSame('http://:::', $command->readOption('host'));
		$this->assertSame('api/index.php', $command->readOption('apiEndpoint'));
	}

	#[TestDox('A host with no authority at all is left alone')]
	public function testHostWithoutAnAuthorityIsLeftAlone(): void
	{
		$command = $this->prepareWith(['--host=/api/index.php']);

		$this->assertSame('/api/index.php', $command->readOption('host'));
		$this->assertNull($command->readOption('apiEndpoint'));
	}
}
