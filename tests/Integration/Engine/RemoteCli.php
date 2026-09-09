<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Integration\Engine;

use RuntimeException;

/**
 * Runs Akeeba Remote CLI in the disposable container and reports what the shell saw.
 *
 * The software under test is a command line program, so this is its observation point: the arguments in, and the exit
 * code, standard output and standard error out. Nothing is loaded in-process, which is what lets the suite run the
 * tool under a PHP version the test runner is not itself using.
 *
 * @since 3.2.0
 */
class RemoteCli
{
	public function __construct(private array $compose, private string $service) {}

	/**
	 * Runs Remote CLI with these arguments.
	 *
	 * @param   string[]  $arguments  The command line, without the `php remote.php` part
	 * @param   array     $env        Environment variables to set for this run
	 *
	 * @return  CliResult
	 */
	public function run(array $arguments, array $env = []): CliResult
	{
		$inner = [];

		foreach ($env as $name => $value)
		{
			$inner[] = sprintf('%s=%s', $name, escapeshellarg((string) $value));
		}

		$inner[] = 'php';
		$inner[] = '/app/remotecli/remote.php';

		foreach ($arguments as $argument)
		{
			$inner[] = escapeshellarg($argument);
		}

		return $this->exec(['sh', '-c', implode(' ', $inner)], $arguments);
	}

	/**
	 * Runs a plain shell command in the container, for arranging and inspecting the filesystem around a run.
	 */
	public function shell(string $command): CliResult
	{
		return $this->exec(['sh', '-c', $command], [$command]);
	}

	/**
	 * Writes a file inside the container.
	 *
	 * Used for the configuration file tests, which need a ~/.akeebaremotecli that cannot be the one on the host.
	 */
	public function writeFile(string $path, string $contents): void
	{
		$result = $this->exec(
			['sh', '-c', sprintf('mkdir -p %s && cat > %s', escapeshellarg(dirname($path)), escapeshellarg($path))],
			['write ' . $path],
			$contents
		);

		if ($result->exitCode !== 0)
		{
			throw new RuntimeException('Could not write ' . $path . ': ' . $result->describe());
		}
	}

	/**
	 * The PHP version Remote CLI is running under, as the container reports it.
	 */
	public function phpVersion(): string
	{
		return trim($this->exec(['php', '-r', 'echo PHP_VERSION;'], ['php -v'])->stdout);
	}

	/**
	 * Runs something in the container.
	 *
	 * @param   string[]      $inContainer  The command to run
	 * @param   array         $described    What to show in a failure message
	 * @param   string|null   $stdin        Anything to feed the command on standard input
	 */
	private function exec(array $inContainer, array $described, ?string $stdin = null): CliResult
	{
		// -T because there is no terminal here, and PHPUnit's output is not one either.
		$command = array_merge($this->compose, ['exec', '-T', $this->service], $inContainer);

		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		$process = proc_open($command, $descriptors, $pipes);

		if (!is_resource($process))
		{
			throw new RuntimeException('Could not run ' . implode(' ', $command));
		}

		if ($stdin !== null)
		{
			fwrite($pipes[0], $stdin);
		}

		fclose($pipes[0]);

		$stdout = (string) stream_get_contents($pipes[1]);
		$stderr = (string) stream_get_contents($pipes[2]);

		fclose($pipes[1]);
		fclose($pipes[2]);

		$exitCode = proc_close($process);

		return new CliResult($described, $exitCode, $stdout, $stderr);
	}
}
