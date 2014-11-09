<?php
/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */

define('REMOTE_STATUS_DEBUG', 5);
define('REMOTE_STATUS_INFO', 10);
define('REMOTE_STATUS_HEADER', 25);
define('REMOTE_STATUS_WARNING', 50);
define('REMOTE_STATUS_ERROR', 100);

class RemoteUtilsRender
{
	public static function info($message, $force = false)
	{
		$options = RemoteUtilsCli::getInstance();
		$machine = $options->get('machine-readable', 0);

		if ($machine)
		{
			self::renderMachine(REMOTE_STATUS_INFO, $message, $force);
		}
		else
		{
			self::renderHuman(REMOTE_STATUS_INFO, $message, $force);
		}
	}

	public static function error($message)
	{
		$options = RemoteUtilsCli::getInstance();
		$machine = $options->get('machine-readable', 0);

		if ($machine)
		{
			self::renderMachine(REMOTE_STATUS_ERROR, $message);
		}
		else
		{
			self::renderHuman(REMOTE_STATUS_ERROR, $message);
		}
	}

	public static function warning($message)
	{
		$options = RemoteUtilsCli::getInstance();
		$machine = $options->get('machine-readable', 0);

		if ($machine)
		{
			self::renderMachine(REMOTE_STATUS_WARNING, $message);
		}
		else
		{
			self::renderHuman(REMOTE_STATUS_WARNING, $message);
		}
	}

	public static function debug($message)
	{
		$options = RemoteUtilsCli::getInstance();
		$machine = $options->get('machine-readable', 0);

		if ($machine)
		{
			self::renderMachine(REMOTE_STATUS_DEBUG, $message);
		}
		else
		{
			self::renderHuman(REMOTE_STATUS_DEBUG, $message);
		}
	}

	public static function header($message)
	{
		$options = RemoteUtilsCli::getInstance();
		$machine = $options->get('machine-readable', 0);

		if ($machine)
		{
			self::renderMachine(REMOTE_STATUS_HEADER, $message);
		}
		else
		{
			self::renderHuman(REMOTE_STATUS_HEADER, $message);
		}
	}

	public static function renderMachine($status, $message, $force = false)
	{
		switch ($status)
		{
			case REMOTE_STATUS_DEBUG:
				echo "DEBUG";
				break;
			case REMOTE_STATUS_INFO:
				echo "INFO";
				break;
			case REMOTE_STATUS_WARNING:
				echo "WARNING";
				break;
			case REMOTE_STATUS_ERROR:
				echo "ERROR";
				break;
			case REMOTE_STATUS_HEADER:
				echo "HEADER";
				break;
		}
		echo "|$message\n";
	}

	public static function renderHuman($status, $message, $force = false)
	{
		static $quiet = null;
		static $nocolor = null;

		if (is_null($quiet))
		{
			$options = RemoteUtilsCli::getInstance();
			$quiet   = $options->hasOption('quiet');
		}

		if (is_null($nocolor))
		{
			$options = RemoteUtilsCli::getInstance();
			$nocolor = $options->hasOption('nocolour');
			if ( !$nocolor)
			{
				$nocolor = $options->hasOption('nocolor');
			}
		}

		switch ($status)
		{
			case REMOTE_STATUS_DEBUG:
				if ($quiet)
				{
					return;
				}
				echo $nocolor ? $message . "\n" : "\033[0;37m$message\n\033[0m";
				break;

			case REMOTE_STATUS_INFO:
				if ($quiet && !$force)
				{
					return;
				}
				echo $nocolor ? $message . "\n" : "$message\n";
				break;

			case REMOTE_STATUS_WARNING:
				fwrite(STDERR, $nocolor ? $message . "\n" : "\033[0;36m$message\n\033[0m");
				break;

			case REMOTE_STATUS_ERROR:
				if ($nocolor)
				{
					fwrite(STDERR, ($quiet ? "" : "\n" . str_repeat('=', 79) . "\nERROR:\n") . $message . "\n" . ($quiet ? '' : str_repeat('=', 79) . "\n"));
				}
				else
				{
					fwrite(STDERR, "\033[1;31m");
					fwrite(STDERR, ($quiet ? '' : "\n" . str_repeat('=', 79) . "\nERROR:\n") . "\033[0;35m" . $message . "\n" . ($quiet ? '' : "\033[1;31m" . str_repeat('=', 79) . "\n"));
					fwrite(STDERR, "\033[0m");
				}
				break;

			case REMOTE_STATUS_HEADER:
				if ($quiet)
				{
					return;
				}
				echo $nocolor ? $message . "\n" : "\033[4;1;32m$message\n\033[0m";
				break;
		}
	}
}