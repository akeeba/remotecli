<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Application\Kernel;

use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Output\Output;
use Psr\Log\LoggerInterface;

/**
 * The interface for a command
 */
interface CommandInterface
{
	public function __construct(Cli $input, Output $output, LoggerInterface $logger);

	/**
	 * Prepare for the command execution. This is a great place to check for the existence of short options and map them to the
	 * long options expected by your command object.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 */
	public function prepare(): void;

	/**
	 * Executes the command.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 */
	public function execute(): void;
}
