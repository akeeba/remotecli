<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Utility;

abstract class IniParser
{
	/**
	 * Parse an INI file and return an associative array. This monstrosity is required because some so-called hosts
	 * have disabled PHP's parse_ini_file() function for "security reasons". Apparently their blatant ignorance doesn't
	 * allow them to discern between the innocuous parse_ini_file and the potentially dangerous ini_set, leading them to
	 * disable the former and let the latter enabled.
	 *
	 * @param   string  $file              The file, or raw data, to process
	 * @param   bool    $process_sections  True to also process INI sections
	 * @param   bool    $rawData           Am I given raw INI data to process in $file?
	 *
	 * @return  array   An associative array of sections, keys and values
	 */
	public static function parse_ini_file(string $file, bool $process_sections, bool $rawData = false): array
	{
		$isBadHostFile   = !function_exists('parse_ini_file');
		$isBadHostString = !function_exists('parse_ini_string');

		if ($rawData)
		{
			if ($isBadHostString)
			{
				return self::parse_ini_file_php($file, $process_sections, $rawData);
			}

			return parse_ini_string($file, $process_sections, INI_SCANNER_RAW);
		}

		if ($isBadHostFile)
		{
			return self::parse_ini_file_php($file, $process_sections);
		}

		return parse_ini_file($file, $process_sections, INI_SCANNER_RAW);
	}

	/**
	 * A PHP based INI file parser.
	 *
	 * Thanks to asohn ~at~ aircanopy ~dot~ net for posting this handy function on
	 * the parse_ini_file page on http://gr.php.net/parse_ini_file
	 *
	 * @param   string  $file              Filename to process
	 * @param   bool    $process_sections  True to also process INI sections
	 * @param   bool    $rawdata           If true, the $file contains raw INI data, not a filename
	 *
	 * @return    array    An associative array of sections, keys and values
	 */
	static function parse_ini_file_php(string $file, bool $process_sections = false, bool $rawdata = false): array
	{
		$process_sections = ($process_sections !== true) ? false : true;

		if (!$rawdata)
		{
			$ini = file($file);
		}
		else
		{
			$file = str_replace("\r", "", $file);
			$ini  = explode("\n", $file);
		}

		if (!is_array($ini))
		{
			return [];
		}

		if (count($ini) == 0)
		{
			return [];
		}

		$sections = [];
		$values   = [];
		$result   = [];
		$globals  = [];
		$i        = 0;
		foreach ($ini as $line)
		{
			$line = trim($line);
			$line = str_replace("\t", " ", $line);

			// Comments
			if (!preg_match('/^[a-zA-Z0-9[]/', $line))
			{
				continue;
			}

			// Sections
			if ($line[0] == '[')
			{
				$tmp        = explode(']', $line);
				$sections[] = trim(substr($tmp[0], 1));
				$i++;
				continue;
			}

			// Key-value pair
			$lineParts = explode('=', $line, 2);
			if (count($lineParts) != 2)
			{
				continue;
			}
			$key   = trim($lineParts[0]);
			$value = trim($lineParts[1]);
			unset($lineParts);

			if (strstr($value, ";"))
			{
				$tmp = explode(';', $value);
				if (count($tmp) == 2)
				{
					if ((($value[0] != '"') && ($value[0] != "'")) ||
						preg_match('/^".*"\s*;/', $value) || preg_match('/^".*;[^"]*$/', $value) ||
						preg_match("/^'.*'\s*;/", $value) || preg_match("/^'.*;[^']*$/", $value)
					)
					{
						$value = $tmp[0];
					}
				}
				else
				{
					if ($value[0] == '"')
					{
						$value = preg_replace('/^"(.*)".*/', '$1', $value);
					}
					elseif ($value[0] == "'")
					{
						$value = preg_replace("/^'(.*)'.*/", '$1', $value);
					}
					else
					{
						$value = $tmp[0];
					}
				}
			}
			$value = trim($value);
			$value = trim($value, "'\"");

			if ($i == 0)
			{
				if (substr($line, -1, 2) == '[]')
				{
					$globals[$key][] = $value;
				}
				else
				{
					$globals[$key] = $value;
				}
			}
			else
			{
				if (substr($line, -1, 2) == '[]')
				{
					$values[$i - 1][$key][] = $value;
				}
				else
				{
					$values[$i - 1][$key] = $value;
				}
			}
		}

		for ($j = 0; $j < $i; $j++)
		{
			if ($process_sections === true)
			{
				if (isset($sections[$j]) && isset($values[$j]))
				{
					$result[$sections[$j]] = $values[$j];
				}
			}
			else
			{
				if (isset($values[$j]))
				{
					$result[] = $values[$j];
				}
			}
		}

		return $result + $globals;
	}
}
