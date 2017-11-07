<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */

use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Kernel\Dispatcher;
use Akeeba\RemoteCLI\Output\Output;
use Akeeba\RemoteCLI\Output\OutputOptions;
use Akeeba\RemoteCLI\Utility\LocalFile;

// PHP Version check
if (version_compare(PHP_VERSION, '5.5.0', 'lt'))
{
	$yourPHP = PHP_VERSION;
	echo <<< END

! ! !    S T O P    ! ! !

Akeeba Remote CLI requires PHP version 5.5.0 or later.

You are currently using PHP $yourPHP as reported by PHP itself.

Please ugprade PHP and retry running this script.

END;

}

// Enable compatibility with PHAR archives
if (Phar::running(false))
{
	Phar::interceptFileFuncs();
}

// Initialize Composer's autoloader (we use it to autoload our classes, too
/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = include_once __DIR__ . '/vendor/autoload.php';

if ($autoloader === false)
{
	die('You must initialize Composer requirements before running this script.');
}

$autoloader->addPsr4("Akeeba\\RemoteCLI\\", __DIR__);

// Get the options from the CLI parameters and merge the configuration file data
$input = new Cli();
$input->mergeData((new LocalFile())->getConfiguration($input->getCmd('host', 'akeebaremotecli')));

// Create the output object
$output        = new Output(
	new OutputOptions(
		$input->getData()
	),
	$input->getBool('machine-readable', false) ? 'machine' : 'console'
);

// Create the dispatcher with all the commands
$dispatcher = new Dispatcher([
	// TODO Add commands here
], $input, $output);

// Dispatch the application
try
{
	$dispatcher->dispatch();
}
catch (Exception $e)
{
	$output->error($e->getMessage());

	$output->debug("Stack Trace (for debugging):");

	foreach (explode("\n", $e->getTraceAsString()) as $line)
	{
		$output->debug($line);
	}
}
