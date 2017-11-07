<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Kernel;

use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Output\Output;

/**
 * The interface for a command
 */
interface CommandInterface
{
	/**
	 * Returns the name of the command.
	 *
	 * @return  string
	 */
	public function getName();

	/**
	 * Returns the options specifications. Used to print help.
	 *
	 * @return  mixed
	 */
	public function getOptionSpecs();

	/**
	 * Executes the command. Dependencies are passed to the object at this point.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 */
	public function execute(Cli $input, Output $output);
}
