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
	 * Prepare for the command execution. This is a great place to check for the existence of short options and map them to the
	 * long options expected by your command object.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 */
	public function prepare(Cli $input);

	/**
	 * Executes the command.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 */
	public function execute(Cli $input, Output $output);
}
