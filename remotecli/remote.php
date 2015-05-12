<?php
/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */

$phar = Phar::running(false);
if ($phar)
{
	Phar::interceptFileFuncs();
	$path = 'phar://' . basename($phar) . '/';
}
else
{
	$path = realpath(dirname(__FILE__)) . '/';
}

// Load the version number defines
require_once($path . 'arccli_version.php');

// Prepare the magic auto-loader
require_once($path . 'loader.php');

// Initialize command-line option handling
$options = RemoteUtilsCli::getInstance();
$options->setMapping(array(
	'host'             => 'h',
	'secret'           => 's',
	'action'           => 'a',
	'download'         => 'd',
	'delete'           => 'D',
	'verbose'          => 'v',
	'machine-readable' => 'm',
	'filename'         => 'f',
	'encapsulation'    => 'e'
));


if ( !$options->get('machine-readable', 0))
{
	if ($options->license)
	{
		echo file_get_contents('LICENSE.txt');
		echo "\n";
		die();
	}

	// Startup banner
	$arc_version = ARCCLI_VERSION;
	$arc_date    = ARCCLI_DATE;
	if ( !$options->hasOption('quiet'))
	{
		echo <<<ENDBANNER
Akeeba Remote Control CLI $arc_version ($arc_date)
Copyright ©2008-2014 Nicholas K. Dionysopoulos / AkeebaBackup.com
--------------------------------------------------------------------------------
 This program comes with ABSOLUTELY NO WARRANTY. This is Free Software and you
 are  welcome to redistribute  it under  certain conditions. Use  command line
 option --license for details.
--------------------------------------------------------------------------------

ENDBANNER;

	}
}

// Pass the action to the controller
try {
	$controller = new RemoteAppController();
	$controller->setAction($options->action);
	$controller->execute();
} catch(Exception $e) {
	RemoteUtilsRender::error("Error #".$e->getCode().": ".$e->getMessage());
	exit($e->getCode());
}

exit(0);