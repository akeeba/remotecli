<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Integration\Engine;

/**
 * One run of Remote CLI, as the shell saw it.
 *
 * @since 3.2.0
 */
readonly class CliResult
{
	public function __construct(
		public array $command,
		public int $exitCode,
		public string $stdout,
		public string $stderr,
	) {}

	/**
	 * Everything the run printed, on either stream.
	 */
	public function output(): string
	{
		return $this->stdout . $this->stderr;
	}

	/**
	 * The machine-readable output, split into [type, message] pairs.
	 *
	 * The -m format is one record per line, `TYPE|message`, and it is the format anything scripting Remote CLI parses.
	 */
	public function records(): array
	{
		$records = [];

		foreach (explode("\n", trim($this->stdout)) as $line)
		{
			if (!str_contains($line, '|'))
			{
				continue;
			}

			[$type, $message] = explode('|', $line, 2);

			$records[] = [$type, $message];
		}

		return $records;
	}

	/**
	 * The messages of one machine-readable record type, e.g. every INFO line.
	 */
	public function recordsOfType(string $type): array
	{
		return array_values(
			array_map(
				fn(array $record) => $record[1],
				array_filter($this->records(), fn(array $record) => $record[0] === $type)
			)
		);
	}

	/**
	 * A description of the run, for use as a PHPUnit failure message.
	 */
	public function describe(): string
	{
		return sprintf(
			"Command: %s\nExit code: %d\n--- stdout ---\n%s\n--- stderr ---\n%s",
			implode(' ', $this->command),
			$this->exitCode,
			$this->stdout,
			$this->stderr
		);
	}
}
