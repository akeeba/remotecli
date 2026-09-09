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
 * The startup guard which refuses to run on a PHP version outside the supported range.
 *
 * Both bounds are declared once, as composer.json's `require.php` constraint, and written into remote.php by
 * `phing version-constraints`. This checks the guard actually enforces what is declared, under real interpreters
 * rather than by re-implementing the comparison in a test.
 *
 * @since 3.2.0
 */
class PhpVersionGuardTest extends E2ETestCase
{
	/**
	 * A PHP version below the declared minimum, as a Docker image tag.
	 *
	 * 8.1 is the newest version we do NOT support, so it is the one a user is most likely to still be on.
	 */
	private const TOO_OLD = '8.1';

	#[TestDox('Remote CLI runs on the PHP version the stack is built for')]
	public function testRunsOnASupportedVersion(): void
	{
		$version = $this->cli->phpVersion();

		$this->assertTrue(
			version_compare($version, '8.2.0', 'ge') && version_compare($version, '8.7', 'lt'),
			sprintf('The stack should be running a supported PHP, got %s.', $version)
		);

		$this->assertSucceeded($this->cli->run(['help']));
	}

	#[TestDox('Remote CLI refuses to run on a PHP version below the supported minimum')]
	public function testRefusesTooOldAPhp(): void
	{
		$result = $this->runUnderPhp(self::TOO_OLD, ['help']);

		if ($result === null)
		{
			$this->markTestSkipped(
				sprintf('The php:%s-cli image is not available; skipping rather than reporting a false pass.', self::TOO_OLD)
			);
		}

		$this->assertSame(255, $result['exitCode'], $result['output']);
		$this->assertStringContainsString('S T O P', $result['output']);
		$this->assertStringContainsString('requires PHP 8.2.0 or later', $result['output']);

		/**
		 * The refusal has to come before anything else runs. A tool which prints its banner, connects to a site and
		 * then dies on a syntax error somewhere deep has told the user nothing useful.
		 */
		$this->assertStringNotContainsString('ABSOLUTELY NO WARRANTY', $result['output']);
	}

	/**
	 * Runs Remote CLI under an arbitrary PHP version, outside the Compose stack.
	 *
	 * The stack's CLI container is built for a supported version by definition, so testing the refusal needs an
	 * interpreter the stack does not have. Returns NULL when the image cannot be obtained, so that a machine without
	 * it skips rather than failing.
	 *
	 * @return  array{exitCode: int, output: string}|null
	 */
	private function runUnderPhp(string $phpVersion, array $arguments): ?array
	{
		$projectRoot = dirname(__DIR__, 3);
		$image       = sprintf('php:%s-cli', $phpVersion);

		$pull = $this->capture(['docker', 'image', 'inspect', $image]);

		if ($pull['exitCode'] !== 0 && $this->capture(['docker', 'pull', '--quiet', $image])['exitCode'] !== 0)
		{
			return null;
		}

		$command = array_merge(
			['docker', 'run', '--rm', '--network', 'none', '-v', $projectRoot . ':/app:ro', $image, 'php',
			 '/app/remotecli/remote.php'],
			$arguments
		);

		$result = $this->capture($command);

		return ['exitCode' => $result['exitCode'], 'output' => $result['stdout'] . $result['stderr']];
	}

	/**
	 * @return  array{exitCode: int, stdout: string, stderr: string}
	 */
	private function capture(array $command): array
	{
		$process = proc_open(
			$command,
			[0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
			$pipes
		);

		if (!is_resource($process))
		{
			return ['exitCode' => 1, 'stdout' => '', 'stderr' => 'Could not run ' . implode(' ', $command)];
		}

		fclose($pipes[0]);

		$stdout = (string) stream_get_contents($pipes[1]);
		$stderr = (string) stream_get_contents($pipes[2]);

		fclose($pipes[1]);
		fclose($pipes[2]);

		return ['exitCode' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
	}
}
