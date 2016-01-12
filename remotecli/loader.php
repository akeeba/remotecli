<?php
/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */

/**
 * Magically load a class file based on the class name.
 *
 * @param string $class
 *
 * @return bool
 */
function RemoteLoader($class)
{
	$parts = array();

	// ========== Parse class names into part lists
	if (ctype_upper(substr($class, 0, 1)))
	{
		// Parse classnames like RemoteFooBar
		$word  = preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $class);
		$parts = explode('_', $word);
		if (strtolower($parts[0]) == 'remote')
		{
			array_shift($parts);
		}
		else
		{
			return false;
		}
	}
	else
	{
		return false;
	}

	// ========== Get path from parts
	// Initialize with the base path to the application
	$phar = Phar::running(false);
	if ($phar)
	{
		$path = 'phar://' . basename($phar) . '/';
	}
	else
	{
		$path = realpath(dirname(__FILE__)) . '/';
	}

	// Process the base location
	$base = strtolower(array_shift($parts));

	if (file_exists($path . $base . '.php'))
	{
		require_once $path . $base . '.php';

		return true;
	}

	if ( !$phar && !is_dir($path . $base))
	{
		return false;
	}

	$path .= $base;
	if ( !count($parts))
	{
		// Single part identifier loading. Expect foobar to be located in
		// /foobar.php or /foobar/foobar.php
		if (file_exists($path . '.php'))
		{
			require_once $path . '.php';

			return true;
		}
		elseif (file_exists($path . '/' . $base . '.php'))
		{
			require_once $path . '/' . $base . '.php';

			return true;
		}
		else
		{
			return false;
		}
	}
	else
	{
		// Multiple part identifier loading. The last part is the file name,
		// everything else is directories which have to be present either
		// verbatim or (preferred) pluralized.
		$file = array_pop($parts);
		$file = strtolower($file);
		$path .= '/';
		if (count($parts))
		{
			foreach ($parts as $part)
			{
				$part = strtolower($part);
				if (is_dir($path . $part))
				{
					$path .= $part . '/';
				}
				else
				{
					return false;
				}
			}
		}

		$path .= $file . '.php';
		if (file_exists($path))
		{
			require_once($path);

			return true;
		}
		else
		{
			return false;
		}
	}
}

spl_autoload_register('RemoteLoader');