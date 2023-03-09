<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\OLD\RemoteCLI\Kernel;

use Akeeba\OLD\RemoteCLI\Input\Cli;
use Akeeba\OLD\RemoteCLI\Output\Output;

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
	public function prepare(Cli $input): void;

	/**
	 * Executes the command.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 */
	public function execute(Cli $input, Output $output): void;
}
