<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Output;

/**
 * Immutable options for the output adapter.
 *
 * @property-read   bool  $debug             Enable debug mode. Displays DEBUG level messages, implies $quiet=false.
 * @property-read   bool  $quiet             Minimize output to warnings and errors
 * @property-read   bool  $noColor           Strip all color from the output
 * @property-read   bool  $mergeErrorOutput  Output errors to STDOUT instead of STDERR
 */
class OutputOptions
{
	/**
	 * Be quiet: minimizes the output to warnings and errors
	 *
	 * @var   bool
	 */
	private $quiet = false;

	/**
	 * Should I strip the colors from the output?
	 *
	 * @var   bool
	 */
	private $noColor = false;

	/**
	 * True to output errors in STDOUT. If it's set to false (default), errors are output in STDERR instead.
	 *
	 * @var   bool
	 */
	private $mergeErrorOutput = false;

	/**
	 * Is the debug mode enabled?
	 *
	 * @var   bool
	 */
	private $debug = false;

	/**
	 * OutputOptions constructor. The options you pass initialize the immutable object.
	 *
	 * @param   array   $options  The options to initialize the object with
	 * @param   bool    $strict   When enabled, unknown $options keys will throw an exception instead of silently skipped.
	 */
	public function __construct(array $options, $strict = false)
	{
		foreach ($options as $k => $v)
		{
			if (!property_exists($this, $k))
			{
				if ($strict)
				{
					throw new \LogicException(sprintf('Class %s does not have property ‘%s’', __CLASS__, $k));
				}

				continue;
			}

			$this->$k = $v;
		}

		// Map options
		if (isset($options['nocolor']))
		{
			$this->noColor = (bool) $options['nocolor'];
		}

		if (isset($options['mergeerror']))
		{
			$this->mergeErrorOutput = (bool) $options['mergeerror'];
		}

		// The debug flag forcibly turns off quiet mode
		if ($this->debug)
		{
			$this->quiet = false;
		}
	}

	/**
	 * Magic getter, used to implement read only properties.
	 *
	 * @param   string  $name  The name of the property bneing read
	 *
	 * @return  mixed
	 */
	public function __get($name)
	{
		if (property_exists($this, $name))
		{
			return $this->$name;
		}

		throw new \LogicException(sprintf('Class %s does not have property ‘%s’', __CLASS__, $name));
	}


}
