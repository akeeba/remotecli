<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Output;

class Output
{
	// Output types
	public const DEBUG = 5;
	public const INFO = 10;
	public const HEADER = 25;
	public const WARNING = 50;
	public const ERROR = 100;

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
		$this->adapter->writeln(self::INFO, $message, $force);
	}

	public function error($message)
	{
		$this->adapter->writeln(self::ERROR, $message, true);
	}

	public function warning($message)
	{
		$this->adapter->writeln(self::WARNING, $message, true);
	}

	public function debug($message)
	{
		$this->adapter->writeln(self::DEBUG, $message, false);
	}

	public function header($message)
	{
		$this->adapter->writeln(self::HEADER, $message, false);
	}
}
