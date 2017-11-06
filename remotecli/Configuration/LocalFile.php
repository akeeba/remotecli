<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Configuration;

/**
 * Local Configuration File Parser
 *
 * Loads configuration files stored in a local file, by default ~/.akeebaremotecli
 */
class LocalFile
{
	/**
	 * The default configuration filename
	 */
	const defaultFileName = '.akeebaremotecli';

	const defaultSection = 'remote';

	/**
	 * The path to the local file storing the configuration parameters.
	 *
	 * @var  string
	 */
	protected $filePath;

	/**
	 * The configurations read from the file. Each section is its own configuration. Parameters outside a section are
	 * considered part of the defaultSection.
	 *
	 * @var  array
	 */
	protected $configurations = [];

	/**
	 * LocalFile constructor.
	 *
	 * @param   string|null  $filePath  The path to the local filename containing the configuration parameters
	 */
	public function __construct($filePath = null)
	{
		if (empty($filePath))
		{
			$filePath = $this->getDefaultFilepath();
		}

		$this->filePath = $filePath;;

		$this->parse();
	}

	/**
	 * Getter for the file path being currently used.
	 *
	 * @return  null|string  The path to the file
	 */
	public function getFilePath()
	{
		return $this->filePath;
	}

	/**
	 * Returns the contents of a configuration section.
	 *
	 * @param   string|null  $name  The name of the configuration section you want to read
	 *
	 * @return  array
	 */
	public function getConfiguration($name = null)
	{
		if (empty($name))
		{
			$name = self::defaultSection;
		}

		if (!isset($this->configurations[$name]))
		{
			return [];
		}

		return $this->configurations[$name];
	}

	/**
	 * The default file path to read from.
	 *
	 * @return  string
	 */
	public function getDefaultFilepath()
	{
		$home = getenv('HOME');

		/**
		 * You're either on Windows or running under CGI with suPHP. In these cases the HOME environment variable is
		 * empty. I'm going to try and use a file in the current working directory and hope for the best.
		 */
		if (empty($home))
		{
			$home = getcwd();
		}

		return rtrim($home, DIRECTORY_SEPARATOR) . '/' . self::defaultFileName;
	}

	/**
	 * Parses the configuration file, if it exists.
	 */
	private function parse()
	{
		if (!file_exists($this->filePath) || !is_readable($this->filePath))
		{
			return;
		}

		$this->configurations = [];

		// TODO Maybe backport our custom INI parser here...?
		$results = parse_ini_file($this->filePath, true, INI_SCANNER_RAW);

		// This bit is necessary to parse options outside of a section
		foreach ($results as $k => $v)
		{
			if (!is_array($v))
			{
				$this->configurations[self::defaultSection][$k] = $v;

				continue;
			}

			$this->configurations[$k] = $v;
		}
	}
}