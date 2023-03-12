<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Logger;

use Akeeba\RemoteCLI\Application\Output\Output as ApplicationOutput;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Redirects the log messages to the standard output
 */
class Output extends AbstractLogger implements LoggerInterface
{
	public function __construct(private ApplicationOutput $output, private bool $debug = false, private bool $quiet = false)
	{
		$this->debug &= !$this->quiet;
	}

	public function log($level, \Stringable|string $message, array $context = []): void
	{
		// Silent mode: only errors are output
		if ($this->quiet && !in_array($level, [LogLevel::ERROR, LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY]))
		{
			return;
		}

		if (PHP_EOL != "\n")
		{
			$message = str_replace(PHP_EOL, "\n", $message);
		}

		$messages = explode("\n", $message);

		foreach ($messages as $message)
		{
			switch ($level)
			{
				case LogLevel::DEBUG:
					// Debug messages are only output when the debug mode is enabled
					if (!$this->debug)
					{
						return;
					}

					$this->output->debug($message);
					break;

				case LogLevel::INFO:
				case LogLevel::NOTICE:
					$this->output->info($message);
					break;

				case LogLevel::WARNING:
					$this->output->warning($message);
					break;

				case LogLevel::ERROR:
				case LogLevel::CRITICAL:
				case LogLevel::ALERT:
				case LogLevel::EMERGENCY:
					$this->output->error($message);
					break;
			}
		}
	}
}