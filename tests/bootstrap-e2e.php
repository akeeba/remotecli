<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

/**
 * Bootstrap for the integration suite.
 *
 * It loads no application code at all. The software under test is a command line program running in another container,
 * under a PHP version which is not this one, and the only honest way to observe it is to run it and read what it
 * prints. Anything this process loaded from remotecli/ would be a different copy under a different interpreter.
 *
 * All it does is register the suite's own classes and check the stack is actually up, so that forgetting to run
 * tests/docker/run.sh produces one clear sentence instead of a screen of connection failures.
 */

spl_autoload_register(
	function (string $class): void {
		$prefix = 'Akeeba\\RemoteCLI\\Tests\\Integration\\';

		if (!str_starts_with($class, $prefix))
		{
			return;
		}

		$path = __DIR__ . '/Integration/' . strtr(substr($class, strlen($prefix)), '\\', '/') . '.php';

		if (is_file($path))
		{
			require_once $path;
		}
	}
);

$configFile = __DIR__ . '/config.php';

if (!is_file($configFile))
{
	fwrite(
		STDERR,
		"tests/config.php does not exist.\n\n"
		. "The integration suite does not run on its own. Bring the disposable stack up with:\n\n"
		. "    tests/docker/run.sh\n\n"
		. "which generates that file, runs this suite against the stack, and tears it down again.\n"
	);

	exit(1);
}

$configuration = require $configFile;

// Prove the fixture is answering before a single test runs.
$probe = @file_get_contents(
	str_replace('/control.php', '/health.php', $configuration['control']),
	false,
	stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]])
);

if ($probe !== 'OK')
{
	fwrite(
		STDERR,
		sprintf(
			"The fixture web server is not answering on %s.\n\n"
			. "Run tests/docker/run.sh, which brings the stack up before running this suite.\n",
			str_replace('/control.php', '/health.php', $configuration['control'])
		)
	);

	exit(1);
}

$GLOBALS['ARCCLI_E2E_CONFIG'] = $configuration;
