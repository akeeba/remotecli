<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class MultiLogger extends AbstractLogger implements LoggerInterface
{
	public function __construct(private array $loggers)
	{
	}

	public function log($level, \Stringable|string $message, array $context = []): void
	{
		foreach ($this->loggers as $logger)
		{
			if (!$logger instanceof LoggerInterface)
			{
				continue;
			}

			$logger->log($level, $message, $context);
		}
	}

	public function addLogger(LoggerInterface $logger): void
	{
		if (!in_array($logger, $this->loggers, true))
		{
			$this->loggers[] = $logger;
		}
	}
}