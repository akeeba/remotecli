<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Input;

/**
 * Class Input
 *
 * Handles the input to the application and allows developers to manipulate
 * it in a safe manner.
 *
 * @method    integer  getInt($name, $default = null)
 * @method    integer  getInteger($name, $default = null)
 * @method    integer  getUint($name, $default = null)
 * @method    float    getFloat($name, $default = null)
 * @method    float    getDouble($name, $default = null)
 * @method    boolean  getBool($name, $default = null)
 * @method    boolean  getBoolean($name, $default = null)
 * @method    string   getWord($name, $default = null)
 * @method    string   getAlnum($name, $default = null)
 * @method    string   getCmd($name, $default = null)
 * @method    string   getBase64($name, $default = null)
 * @method    string   getString($name, $default = null)
 * @method    string   getHtml($name, $default = null)
 * @method    string   getPath($name, $default = null)
 * @method    string   getUsername($name, $default = null)
 */
class Input implements \Countable
{
	/** @var   Filter  Filter object to use. */
	protected $filter = null;

	/** @var   array  Input data */
	protected $data = [];

	/** @var   array  Input objects */
	protected $inputs = [];

	/** @var   array  Input options */
	protected $options = [];

	/** @var bool Flag to detect if I already imported all the inputs */
	private static $inputsLoaded = false;

	/**
	 * Constructor
	 *
	 * @param   array|null  $source   Source data (Optional, default is $_REQUEST)
	 * @param   array       $options  Options for the Input object
	 */
	public function __construct(?array $source = null, array $options = [])
	{
		$this->options = $options;

		if (isset($options['filter']))
		{
			$this->filter = $options['filter'];
		}
		else
		{
			$this->filter = Filter::getInstance();
		}

		// Should I reference the superglobal variable $_REQUEST?
		$referenceSuperglobal = is_null($source);

		// Do I need to work around magic_quotes_gpc?
		if (isset($options['magicQuotesWorkaround']))
		{
			$magicQuotesWorkaround = $options['magicQuotesWorkaround'];
		}
		else
		{
			// If there was no source specified, always try working around magic_quotes_gpc on PHP 5.3
			$magicQuotesWorkaround = $referenceSuperglobal;
		}

		// On PHP 5.3 we have the plague of magic_quotes_gpc. Let's try working around it, if we are told so.
		if (version_compare(PHP_VERSION, '5.4.0', 'lt') && $magicQuotesWorkaround && function_exists('ini_get') && ini_get('magic_quotes_gpc'))
		{
			if ($referenceSuperglobal)
			{
				$source               = self::cleanMagicQuotes($_REQUEST);
				$referenceSuperglobal = false;
			}
			else
			{
				$source = self::cleanMagicQuotes($source);
			}
		}

		if ($referenceSuperglobal)
		{
			$this->data = &$_REQUEST;
		}
		else
		{
			$this->data = $source;
		}
	}

	public static function cleanMagicQuotes(array $source): array
	{
		$temp = [];

		foreach ($source as $k => $v)
		{
			if (is_array($v))
			{
				$v = self::cleanMagicQuotes($v);
			}
			else
			{
				$v = stripslashes($v);
			}

			$temp[$k] = $v;
		}

		return $temp;
	}

	/**
	 * Magic method to get an input object
	 *
	 * @param   mixed  $name  Name of the input object to retrieve.
	 *
	 * @return  Input  The request input object
	 */
	public function __get($name)
	{
		if (isset($this->inputs[$name]))
		{
			return $this->inputs[$name];
		}

		$className = '\\Akeeba\\RemoteCLI\\Input\\' . ucfirst($name);

		if (class_exists($className))
		{
			$this->inputs[$name] = new $className(null);

			return $this->inputs[$name];
		}

		$superGlobal = '_' . strtoupper($name);

		if (isset($GLOBALS[$superGlobal]))
		{
			$this->inputs[$name] = new Input($GLOBALS[$superGlobal]);

			return $this->inputs[$name];
		}

		return null;
	}

	/**
	 * Get the number of variables.
	 *
	 * @return  integer  The number of variables in the input.
	 *
	 * @see     \Countable::count()
	 */
	public function count(): int
	{
		return count($this->data);
	}

	/**
	 * Gets a value from the input data.
	 *
	 * @param   string  $name     Name of the value to get.
	 * @param   mixed   $default  Default value to return if variable does not exist.
	 * @param   string  $filter   Filter to apply to the value.
	 *
	 * @return  mixed  The filtered input value.
	 */
	public function get(string $name, $default = null, string $filter = 'cmd')
	{
		if (isset($this->data[$name]))
		{
			return $this->filter->clean($this->data[$name], $filter);
		}

		return $default;
	}

	/**
	 * Gets an array of values from the request.
	 *
	 * @param   array       $vars        Associative array of keys and filter types to apply.
	 * @param   array|null  $datasource  Array to retrieve data from, or null
	 *
	 * @return  mixed  The filtered input data.
	 */
	public function getArray(array $vars, ?array $datasource = null)
	{
		$results = [];

		foreach ($vars as $k => $v)
		{
			if (is_array($v))
			{
				if (is_null($datasource))
				{
					$results[$k] = $this->getArray($v, $this->get($k, null, 'array'));
				}
				else
				{
					$results[$k] = $this->getArray($v, $datasource[$k]);
				}
			}
			else
			{
				if (is_null($datasource))
				{
					$results[$k] = $this->get($k, null, $v);
				}
				elseif (isset($datasource[$k]))
				{
					$results[$k] = $this->filter->clean($datasource[$k], $v);
				}
				else
				{
					$results[$k] = $this->filter->clean(null, $v);
				}
			}
		}

		return $results;
	}

	/**
	 * Sets a value
	 *
	 * @param   string  $name   Name of the value to set.
	 * @param   mixed   $value  Value to assign to the input.
	 *
	 * @return  void
	 */
	public function set(string $name, $value): void
	{
		$this->data[$name] = $value;
	}

	/**
	 * Define a value. The value will only be set if there's no value for the name or if it is null.
	 *
	 * @param   string  $name   Name of the value to define.
	 * @param   mixed   $value  Value to assign to the input.
	 *
	 * @return  void
	 */
	public function def(string $name, $value): void
	{
		if (isset($this->data[$name]))
		{
			return;
		}

		$this->data[$name] = $value;
	}

	/**
	 * Magic method to get filtered input data.
	 *
	 * @param   string  $name       Name of the filter type prefixed with 'get'.
	 * @param   array   $arguments  [0] The name of the variable [1] The default value.
	 *
	 * @return  mixed   The filtered input value.
	 */
	public function __call(string $name, array $arguments)
	{
		if (substr($name, 0, 3) == 'get')
		{

			$filter = substr($name, 3);

			$default = null;

			if (isset($arguments[1]))
			{
				$default = $arguments[1];
			}

			return $this->get($arguments[0], $default, $filter);
		}

		return null;
	}

	/**
	 * Gets the request method.
	 *
	 * @return  string   The request method.
	 */
	public function getMethod(): string
	{
		$method = strtoupper($_SERVER['REQUEST_METHOD']);

		return $method;
	}

	/**
	 * Method to load all of the global inputs.
	 *
	 * @return  void
	 */
	protected function loadAllInputs(): void
	{
		if (!self::$inputsLoaded)
		{
			// Load up all the globals.
			foreach ($GLOBALS as $global => $data)
			{
				// Check if the global starts with an underscore.
				if (strpos($global, '_') === 0)
				{
					// Convert global name to input name.
					$global = strtolower($global);
					$global = substr($global, 1);

					// Get the input.
					$this->$global;
				}
			}

			self::$inputsLoaded = true;
		}
	}

	/**
	 * Returns the (raw) input data as a hash array
	 *
	 * @return  array
	 */
	public function getData(): array
	{
		return (array) $this->data;
	}

	/**
	 * Replaces the (raw) input data with the given array
	 *
	 * @param   array|object  $data  The raw input data to use
	 *
	 * @return  void
	 */
	public function setData($data): void
	{
		$this->data = (array) $data;
	}

	/**
	 * Merges $data into the raw input data
	 *
	 * @param   array|object  $data  The raw input data to merge
	 */
	public function mergeData($data): void
	{
		$data       = (array) $data;
		$this->data = array_replace_recursive($this->data, $data);
	}
}
