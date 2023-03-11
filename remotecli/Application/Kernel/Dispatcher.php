<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Kernel;

use Akeeba\RemoteCLI\Application\Exception\InvalidCommand;
use Akeeba\RemoteCLI\Application\Exception\NoCommand;
use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Logger\File;
use Akeeba\RemoteCLI\Application\Logger\MultiLogger;
use Akeeba\RemoteCLI\Application\Logger\Output as OutputLogger;
use Akeeba\RemoteCLI\Application\Output\Output;
use Psr\Log\LoggerInterface;

class Dispatcher
{
	protected string $defaultCommand = '';

	protected string $command;

	protected LoggerInterface $logger;

	/**
	 * Public constructor
	 *
	 * @param   CommandInterface[]|string[]  $commands  Available commands
	 * @param   Cli                          $input     Input handler
	 * @param   Output                       $output    Output handler
	 */
	public function __construct(private array $commands, private Cli $input, private Output $output)
	{
		$this->logger = $this->createLogger($input, $output);

		$this->commands = array_map(
			fn($x) => $x->setLogger($this->logger),
			array_filter(
				array_map(
					fn($x) => new $x,
					$this->commands
				)
			)
		);

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
	public function dispatch(): void
	{
		$this->showBanner();

		// Map legacy --license option to the license command
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

		$this->logger->debug(sprintf('Executing command %s', $this->command));

		$commandObject->prepare($this->input);
		$commandObject->execute($this->input, $this->output);
	}

	/**
	 * Get the default command set up in the dispatcher
	 *
	 * @return  string
	 */
	public function getDefaultCommand(): string
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
	public function setDefaultCommand(string $defaultCommand): void
	{
		$this->defaultCommand = $defaultCommand;
	}

	public function getLogger(): LoggerInterface
	{
		return $this->logger;
	}

	/**
	 * Determine the command to execute.
	 *
	 * For backwards compatibility reasons we first check the --action option. If it's empty we will use the first
	 * argument. Note that if you specify --action then the argument is ignored.
	 */
	protected function determineCommand(): void
	{
		$command = $this->input->getCmd('action', null);

		if (empty($command))
		{
			$args = $this->input->getArguments();

			if (is_array($args) && !empty($args))
			{
				$command = array_shift($args);
			}
		}

		// Not redundant; if no command is set we have to use the default.
		$this->command = $command ?: $this->defaultCommand;
	}

	/**
	 * Display the version banner, unless the --quiet option has been supplied.
	 *
	 * @return  void
	 */
	protected function showBanner(): void
	{
		if ($this->input->getBool('quiet'))
		{
			return;
		}

		if ($this->input->getBool('m') || $this->input->getBool('machine-readable'))
		{
			return;
		}

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

	private function createLogger(Cli $input, Output $output): LoggerInterface
	{
		$debug  = $input->getBool('debug', false);
		$quiet = $input->getBool('quiet', false);

		$logger = new OutputLogger($output, $debug, $quiet);

		if ($debug)
		{
			// ALso log to a file
			$logger = new MultiLogger([
				$logger,
				new File(getcwd() . '/remotecli_log.txt')
			]);
		}

		return $logger;
	}
}
