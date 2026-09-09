<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

/**
 * Bootstrap for the unit test suite.
 *
 * It loads the application's classes and nothing else. No command is dispatched, no HTTP request is made, and no
 * configuration file is read: everything the unit suite exercises has to run in isolation, and anything which cannot
 * belongs in the integration suite instead.
 */

use Composer\CaBundle\CaBundle;

/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = require_once __DIR__ . '/../remotecli/vendor/autoload.php';

if ($autoloader === false)
{
	fwrite(STDERR, "Run 'composer install' before running the test suite.\n");

	exit(1);
}

// The application's own PSR-4 prefix, registered the same way remote.php registers it.
$autoloader->addPsr4('Akeeba\\RemoteCLI\\', dirname(__DIR__) . '/remotecli/', true);
$autoloader->addPsr4('Akeeba\\RemoteCLI\\Tests\\', __DIR__ . '/', true);

/**
 * The version file is generated at build time and is not in the repository, so a fresh checkout does not have one.
 * Fall back to the template it is generated from. Its ##VERSION## and ##DATE## placeholders are of no interest to a
 * test, and reading ARCCLI_MINAPI from the same file the build reads means the two can never disagree.
 */
$versionFile = dirname(__DIR__) . '/remotecli/arccli_version.php';
$versionFile = is_file($versionFile) ? $versionFile : dirname(__DIR__) . '/build/templates/arccli_version.php';

require_once $versionFile;

/**
 * remote.php defines this after writing the bundled CA bundle to a temporary file, so that cURL can read it from
 * outside the PHAR. A test has no PHAR to work around; the bundled path itself will do.
 */
if (!defined('AKEEBA_CACERT_PEM'))
{
	define('AKEEBA_CACERT_PEM', CaBundle::getBundledCaBundlePath());
}
