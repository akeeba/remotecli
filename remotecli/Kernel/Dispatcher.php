<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Kernel;


use Akeeba\RemoteCLI\Exception\InvalidCommand;
use Akeeba\RemoteCLI\Exception\NoCommand;
use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Output\Output;

class Dispatcher
{
	/** @var   string  The name of the default command, in case none is specified */
	protected $defaultCommand = '';

	/** @var   Cli  Input variables */
	protected $input = array();

	/** @var   Output  Output handler */
	protected $output;

	/** @var string  The command which will be routed by the dispatcher */
	protected $command;

	/** @var CommandInterface[] */
	protected $commands = [];

	/**
	 * Public constructor
	 *
	 * @param   CommandInterface[]|string[]  $commands  Available commands
	 * @param   Cli                          $input     Input handler
	 * @param   Output                       $output    Output handler
	 */
	public function __construct(array $commands, Cli $input, Output $output)
	{
		$this->input    = $input;
		$this->output   = $output;
		$this->commands = [];

		foreach ($commands as $command)
		{
			if (is_object($command) && ($command instanceof CommandInterface))
			{
				$this->commands[] = $command;

				continue;
			}

			if (!is_string($command))
			{
				continue;
			}

			if (!is_subclass_of($command, CommandInterface::class))
			{
				continue;
			}

			$this->commands[] = new $command;
		}

		$this->determineCommand();
	}

	/**
	 * The main code of the Dispatcher. It spawns the necessary controller and
	 * runs it.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 */
	public function dispatch()
	{
		if (!$this->input->getBool('quiet'))
		{
			$version = ARCCLI_VERSION;
			$date    = ARCCLI_DATE;
			$year    = gmdate('Y');

			echo <<< BANNER
Akeeba Remote Control CLI $version ($date)
Copyright ©2006-$year Nicholas K. Dionysopoulos / Akeeba Ltd.
--------------------------------------------------------------------------------
 This program comes with ABSOLUTELY NO WARRANTY. This is Free Software and you
 are welcome to redistribute it under certain conditions. Use command license
 for details.
--------------------------------------------------------------------------------

BANNER;

		}

		// TODO Map legacy --license option to the license command
		if ($this->input->getBool('license', false))
		{
			$this->command = 'license';
		}

		if (empty($this->command))
		{
			$this->command = $this->defaultCommand;
		}

		if (empty($this->command))
		{
			throw new NoCommand();
		}

		$commandObject = null;

		/** @var CommandInterface $o */
		foreach ($this->commands as $o)
		{
			$parts       = explode('\\', get_class($o));
			$commandName = array_pop($parts);

			if (strtolower($commandName) != strtolower($this->command))
			{
				continue;
			}

			$commandObject = $o;

			break;
		}

		if (is_null($commandObject))
		{
			throw new InvalidCommand($this->command);
		}

		$commandObject->execute($this->input, $this->output);
	}

	/**
	 * Get the default command set up in the dispatcher
	 *
	 * @return  string
	 */
	public function getDefaultCommand()
	{
		return $this->defaultCommand;
	}

	/**
	 * Set the default command
	 *
	 * @param   string  $defaultCommand
	 *
	 * @return  void
	 */
	public function setDefaultCommand($defaultCommand)
	{
		$this->defaultCommand = $defaultCommand;
	}

	/**
	 * Determine the command to execute.
	 *
	 * For backwards compatibility reasons we first check the --action option. If it's empty we will use the first
	 * argument. Note that if you specify --action then the argument is ignored.
	 */
	protected function determineCommand()
	{
		$this->command = $this->input->getCmd('action', null);

		if (empty($this->command))
		{
			$args = $this->input->getArguments();

			if (is_array($args) && !empty($args))
			{
				$this->command = array_shift($args);
			}
		}

		// Not redundant; if no command is set we have to use the default.
		if (empty($this->command))
		{
			$this->command = $this->defaultCommand;
		}
	}
}
