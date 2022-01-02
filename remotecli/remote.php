<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

use Akeeba\RemoteCLI\Command\Backup;
use Akeeba\RemoteCLI\Command\BackupInfo;
use Akeeba\RemoteCLI\Command\Delete;
use Akeeba\RemoteCLI\Command\Deletefiles;
use Akeeba\RemoteCLI\Command\Download;
use Akeeba\RemoteCLI\Command\License;
use Akeeba\RemoteCLI\Command\Listbackups;
use Akeeba\RemoteCLI\Command\PHP;
use Akeeba\RemoteCLI\Command\Profiles;
use Akeeba\RemoteCLI\Command\Test;
use Akeeba\RemoteCLI\Command\Update;
use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Kernel\Dispatcher;
use Akeeba\RemoteCLI\Output\Output;
use Akeeba\RemoteCLI\Output\OutputOptions;
use Akeeba\RemoteCLI\Utility\LocalFile;

// PHP Version check
if (version_compare(PHP_VERSION, '7.2.0', 'lt'))
{
	$yourPHP = PHP_VERSION;
	echo <<< END

! ! !    S T O P    ! ! !

Akeeba Remote CLI requires PHP version 7.2.0 or later.

You are currently using PHP $yourPHP as reported by PHP itself.

Please ugprade PHP and retry running this script.

END;

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

// Let's use the tmpfile trick: in this way the file will removed once the $temp_cacert goes out of scope
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
	PHP::class,
	License::class,
	Test::class,
	Backup::class,
	Download::class,
	Deletefiles::class,
	Delete::class,
	Profiles::class,
	Listbackups::class,
	Update::class,
	BackupInfo::class,
], $input, $output);

// Dispatch the application
try
{
	$dispatcher->dispatch();
}
catch (Exception $e)
{
	$output->error(sprintf('Error #%d - %s', $e->getCode(), $e->getMessage()));

	$output->debug("Stack Trace (for debugging):");

	foreach (explode("\n", $e->getTraceAsString()) as $line)
	{
		$output->debug($line);
	}

	exit($e->getCode());
}
