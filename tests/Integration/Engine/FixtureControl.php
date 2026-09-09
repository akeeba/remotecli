<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Integration\Engine;

use RuntimeException;

/**
 * The fixture's back door.
 *
 * It resets the server to a known state before each test, arms the behaviours a real site cannot be asked for on
 * demand, and — most usefully — reports every request the server actually received, so that "Remote CLI never asked
 * for that" can be asserted about the world rather than about a log line.
 *
 * @since 3.2.0
 */
class FixtureControl
{
	public function __construct(private string $url) {}

	public function reset(): void
	{
		$this->call(['action' => 'reset']);
	}

	/**
	 * Narrows the API versions the fixture will answer on.
	 *
	 * A fixture which only speaks v2 is what a site running Akeeba Backup 9.0 looks like from the outside, and it is
	 * the only way to test that version negotiation actually negotiates.
	 *
	 * @param   int[]  $versions
	 */
	public function speakOnly(array $versions): void
	{
		$this->call(['action' => 'versions', 'versions' => implode(',', $versions)]);
	}

	/**
	 * Arms a behaviour for the next few requests.
	 *
	 * @param   string  $kind   'junk' to bury the answer in PHP warnings
	 * @param   string  $where  'before', 'after', 'both' or 'hashes'
	 * @param   int     $count  How many requests it applies to
	 */
	public function arm(string $kind, string $where = 'before', int $count = 1): void
	{
		$this->call(['action' => 'arm', 'kind' => $kind, 'where' => $where, 'count' => $count]);
	}

	/**
	 * Every request the fixture has been sent since the last reset.
	 */
	public function requests(): array
	{
		return (array) $this->call(['action' => 'requests']);
	}

	/**
	 * The API versions the fixture was actually asked to speak, in the order it was asked, without repeats.
	 */
	public function apiVersionsTried(): array
	{
		return array_values(array_unique(array_map(fn(array $r) => (int) $r['apiVersion'], $this->requests())));
	}

	/**
	 * The credential presented on the last request, as [type, value].
	 *
	 * The type is 'token', 'secret' or 'none'. This is what makes "the token was preferred" an assertion about the
	 * bytes on the wire rather than about a message Remote CLI printed about itself.
	 */
	public function lastCredential(): array
	{
		$requests = $this->requests();
		$last     = end($requests);

		if ($last === false)
		{
			return ['none', null];
		}

		if (!empty($last['headers']['X-Joomla-Token']))
		{
			return ['token', $last['headers']['X-Joomla-Token']];
		}

		if (!empty($last['headers']['X-Akeeba-Auth']))
		{
			return ['secret', $last['headers']['X-Akeeba-Auth']];
		}

		return ['none', null];
	}

	private function call(array $query): mixed
	{
		$url      = $this->url . '?' . http_build_query($query);
		$response = @file_get_contents(
			$url,
			false,
			stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]])
		);

		if ($response === false)
		{
			throw new RuntimeException('The fixture control endpoint did not answer: ' . $url);
		}

		return json_decode($response, true);
	}
}
