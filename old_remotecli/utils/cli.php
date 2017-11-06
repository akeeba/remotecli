<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteUtilsCli
{
	/**
	 * @var array Options map, maps short to long options
	 */
	private $optionMap = array();

	/**
	 * @var array Settings passed in the command line
	 */
	private $settings = array();

	/**
	 * Public constructor. Automatically fetches CLI arguments and puts them in
	 * the settings array.
	 */
	public function __construct()
	{
		global $argc, $argv;

		// Workaround for PHP-CGI
		if ( !isset($argc) && !isset($argv))
		{
			$query = "";
			if ( !empty($_GET))
			{
				foreach ($_GET as $k => $v)
				{
					$query .= " $k";
					if ($v != "")
					{
						$query .= "=$v";
					}
				}
			}
			$query = ltrim($query);
			$argv  = explode(' ', $query);
			$argc  = count($argv);
		}

		$currentName = "";
		$options     = array();

		for ($i = 1; $i < $argc; $i++)
		{
			$argument = $argv[$i];
			if (strpos($argument, "-") === 0)
			{
				if ((substr($argument, 0, 2) != '--') && (strlen($argument) != 2))
				{
					// Parse -xyz concatenated arguments as -x -y -z
					$argument  = ltrim($argument, '-');
					$arguments = str_split($argument);
					foreach ($arguments as $x)
					{
						$options[$x] = 1;
					}
				}
				else
				{
					$argument = ltrim($argument, '-');
					if (strstr($argument, '='))
					{
						list($name, $value) = explode('=', $argument, 2);
					}
					else
					{
						$name  = $argument;
						$value = null;
					}
					$currentName = $name;
					if ( !isset($options[$currentName]) || ($options[$currentName] == null))
					{
						$options[$currentName] = 1;
					}
				}
			}
			else
			{
				$value = $argument;
			}

			if (( !is_null($value)) && ( !is_null($currentName)))
			{
				$options[$currentName] = $value;
			}
		}

		$this->settings = $options;
	}

	/**
	 * Gets a Singleton instance of ourselves
	 *
	 * @return RemoteUtilsCLI
	 */
	public static function getInstance()
	{
		static $instance = null;

		if ( !is_object($instance))
		{
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Sets short to long option mapping. You can either give it two options,
	 * short and long tag or pass an associative long=>short array as the first
	 * parameter and skip the second one.
	 *
	 * @param string|array $short
	 * @param string|null  $long
	 */
	public function setMapping($short, $long = null)
	{
		if (is_null($long) && is_array($short))
		{
			$this->optionMap = array_merge($this->optionMap, $short);
		}
		elseif (is_null($long) && !is_array($short))
		{
			throwError(__CLASS__ . '::' . __METHOD__ . '() -- Invalid operands passed');
		}
		else
		{
			$this->optionMap[$long] = $short;
		}
	}

	/**
	 * Get a CLI setting
	 *
	 * @param string $option  The (long) option name to retrieve
	 * @param mixed  $default The default value, if it's not specified
	 *
	 * @return mixed The value of the setting
	 */
	public function get($option, $default = null)
	{
		$value = $this->$option;
		if (is_null($value))
		{
			$value = $default;
		}

		return $value;
	}

	public function hasOption($option)
	{
		if (array_key_exists($option, $this->settings))
		{
			return true;
		}
		else
		{
			if (array_key_exists($option, $this->optionMap))
			{
				$realname = $this->optionMap[$option];
				if (array_key_exists($realname, $this->settings))
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Overrides a command line option
	 *
	 * @param string $option
	 * @param mixed  $value
	 */
	public function set($option, $value)
	{
		$this->settings[$option] = $value;
	}

	/**
	 * A magic function to allow retrieving OptionX by doing $x = $o->OptionX
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->settings))
		{
			return $this->settings[$name];
		}
		else
		{
			if (array_key_exists($name, $this->optionMap))
			{
				$realname = $this->optionMap[$name];
				if (array_key_exists($realname, $this->settings))
				{
					return $this->settings[$realname];
				}
			}
		}

		return null;
	}
}