<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Output;

class Output
{
	// Output types
	const DEBUG = 5;
	const INFO = 10;
	const HEADER = 25;
	const WARNING = 50;
	const ERROR = 100;

	/**
	 * The interface used internally
	 *
	 * @var  OutputAdapterInterface
	 */
	protected $adapter = null;

	/**
	 * The output options
	 *
	 * @var  OutputOptions
	 */
	protected $options;

	public function __construct(OutputOptions $options, $adapter = 'console')
	{
		$adapterClass = __NAMESPACE__ . '\\' . ucfirst($adapter);

		if (!class_exists($adapterClass))
		{
			throw new \InvalidArgumentException(sprintf('Unknown output adapter ‘%s’.', $adapter));
		}

		if (!is_subclass_of($adapterClass, __NAMESPACE__ . '\\OutputAdapterInterface'))
		{
			throw new \InvalidArgumentException(sprintf('Invalid output adapter interface ‘%s’', $adapter));
		}

		$this->adapter = new $adapterClass($options);

		$this->options = $options;
	}

	public function info($message, $force = false)
	{
		// TODO Add logging

		$this->adapter->writeln(self::INFO, $message, $force);
	}

	public function error($message)
	{
		// TODO Add logging

		$this->adapter->writeln(self::ERROR, $message, true);
	}

	public function warning($message)
	{
		// TODO Add logging

		$this->adapter->writeln(self::WARNING, $message, true);
	}

	public function debug($message)
	{
		// TODO Add logging

		$this->adapter->writeln(self::DEBUG, $message, false);
	}

	public function header($message)
	{
		$this->adapter->writeln(self::HEADER, $message, false);
	}
}
