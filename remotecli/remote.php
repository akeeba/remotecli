<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

use Akeeba\RemoteCLI\Application\Command\Backup;
use Akeeba\RemoteCLI\Application\Command\BackupInfo;
use Akeeba\RemoteCLI\Application\Command\Delete;
use Akeeba\RemoteCLI\Application\Command\Deletefiles;
use Akeeba\RemoteCLI\Application\Command\Download;
use Akeeba\RemoteCLI\Application\Command\Help;
use Akeeba\RemoteCLI\Application\Command\License;
use Akeeba\RemoteCLI\Application\Command\Listbackups;
use Akeeba\RemoteCLI\Application\Command\PHP;
use Akeeba\RemoteCLI\Application\Command\ProfileExport;
use Akeeba\RemoteCLI\Application\Command\ProfileImport;
use Akeeba\RemoteCLI\Application\Command\Profiles;
use Akeeba\RemoteCLI\Application\Command\Test;
use Akeeba\RemoteCLI\Application\Command\Update;
use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Kernel\Dispatcher;
use Akeeba\RemoteCLI\Application\Output\Output;
use Akeeba\RemoteCLI\Application\Output\OutputOptions;
use Akeeba\RemoteCLI\Application\Utility\LocalFile;

/**
 * PHP version check.
 *
 * Both bounds are written here by the build script, from the `require.php` constraint in composer.json. Do not edit
 * them by hand; run `phing version-constraints` instead. $maximumPhp is the first PHP version we do NOT support,
 * which is why it is compared with 'ge' rather than 'gt'.
 */
$minimumPhp = '8.2.0';
$maximumPhp = '8.7';

if (version_compare(PHP_VERSION, $minimumPhp, 'lt') || version_compare(PHP_VERSION, $maximumPhp, 'ge'))
{
	$yourPHP = PHP_VERSION;
	echo <<< END

! ! !    S T O P    ! ! !

Akeeba Remote CLI requires PHP $minimumPhp or later. It does not run on PHP $maximumPhp or later.

You are currently using PHP $yourPHP as reported by PHP itself.

Please use a supported PHP version and retry running this script.


END;

	exit(255);
}

// Enable compatibility with PHAR archives
if (Phar::running(false))
{
	Phar::interceptFileFuncs();
}

// Load the Remote CLI version file
require_once __DIR__ . '/arccli_version.php';

// Initialize Composer's autoloader (we use it to autoload our classes, too
/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = include_once __DIR__ . '/vendor/autoload.php';

if ($autoloader === false)
{
	die('You must initialize Composer requirements before running this script.');
}

$autoloader->addPsr4("Akeeba\\RemoteCLI\\", __DIR__, true);

// Get the options from the CLI parameters and merge the configuration file data
$input = new Cli();
$input->mergeData((new LocalFile())->getConfiguration($input->getCmd('host', 'akeebaremotecli')));

// Create the output object
$machineReadable = $input->getBool('m', false) || $input->getBool('machine-readable', false);
$output          = new Output(
	new OutputOptions(
		$input->getData()
	),
	$machineReadable ? 'machine' : 'console'
);

// cURL is not working nice with phar:// wrappers. This means that we have to manually create a temp file outside the
// package and supply it to cURL
$cacert_path    = \Composer\CaBundle\CaBundle::getBundledCaBundlePath();
$cacertContents = file_get_contents($cacert_path);

// Let's use the tmpfile trick: in this way the file will be removed once the $temp_cacert goes out of scope
$temp_cacert = tmpfile();
$temp_cacert_path = stream_get_meta_data($temp_cacert)['uri'];

fwrite($temp_cacert, $cacertContents);

// If there's a certificate specified in the command line we'll append it to the temporary cacert.pem
$certFile = $input->get('certificate', '', 'raw');

if (!empty($certFile) && is_readable($certFile))
{
	fwrite($temp_cacert,"\n\n");
	fwrite($temp_cacert,file_get_contents($certFile));
}

define('AKEEBA_CACERT_PEM', $temp_cacert_path);

// Create the dispatcher with all the commands
$dispatcher = new Dispatcher([
	Help::class,
	PHP::class,
	License::class,
	Test::class,
	Backup::class,
	BackupInfo::class,
	Download::class,
	Deletefiles::class,
	Delete::class,
	Profiles::class,
	ProfileExport::class,
	ProfileImport::class,
	Listbackups::class,
	Update::class,
], $input, $output);

// Dispatch the application
try
{
	$dispatcher->setDefaultCommand('help');
	$dispatcher->dispatch();
}
catch (Exception $e)
{
	$dispatcher->getLogger()->error(sprintf('Error #%d - %s', $e->getCode(), $e->getMessage()));

	$dispatcher->getLogger()->debug("Stack Trace (for debugging):");

	foreach (explode("\n", $e->getTraceAsString()) as $line)
	{
		$dispatcher->getLogger()->debug($line);
	}

	exit($e->getCode());
}
