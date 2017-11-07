<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Output;

/**
 * Class for machine readable output
 */
class Machine implements OutputAdapterInterface
{
	/**
	 * Output options
	 *
	 * @var   OutputOptions
	 */
	private $options;

	/**
	 * Console constructor.
	 *
	 * @param   OutputOptions  $options  The output configuration options
	 */
	public function __construct(OutputOptions $options)
	{
		$this->options         = $options;
	}

	public function writeln($type, $message, $force = false)
	{
		$quiet = $this->options->quiet && !$force;

		if ($quiet)
		{
			return;
		}

		switch ($type)
		{
			case Output::HEADER:
				$message = sprintf('HEADER|%s', $message);
				break;

			case Output::DEBUG:
				$message = sprintf('DEBUG|%s', $message);
				break;

			case Output::INFO:
				$message = sprintf('INFO|%s', $message);
				break;

			case Output::WARNING:
				$message = sprintf('WARNING|%s', $message);
				break;

			case Output::ERROR:
				$message = sprintf('ERROR|%s', $message);
				break;
		}

		// Finally, print out the message itself.
		fputs(STDOUT, $message . PHP_EOL);
	}
}
