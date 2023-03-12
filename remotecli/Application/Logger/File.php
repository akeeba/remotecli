<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Throwable;

class File extends AbstractLogger implements LoggerInterface
{
	private $fp;

	public function __construct(private string $file, bool $resetFile = true)
	{
		$this->openFile($this->file, $resetFile);
	}

	public function __destruct()
	{
		$this->closeFile();
	}

	/**
	 * @inheritDoc
	 */
	public function log($level, \Stringable|string $message, array $context = []): void
	{
		if (!is_resource($this->fp))
		{
			return;
		}

		if (PHP_EOL != "\n")
		{
			$message = str_replace(PHP_EOL, "\n", $message);
		}

		$messages = explode("\n", $message);

		if (($context['exception'] ?? null) instanceof Throwable)
		{
			/** @var Throwable $exception */
			$exception = $context['exception'];
			$exceptionMessage = sprintf(
				"Exception #%d (%s): %s [%s:%d]",
				$exception->getCode(),
				get_class($exception),
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine()
			);

			array_unshift($messages, $exceptionMessage);
		}

		foreach ($messages as $message)
		{
			fputs(
				$this->fp,
				sprintf(
					'%-10s| %-20s| %s%s',
					strtoupper($level),
					(new \DateTime())->format('Y-m-d H:i:s'),
					$message,
					PHP_EOL
				)
			);
		}
	}

	private function openFile(string $file, bool $resetFile): void
	{
		$mode = $resetFile ? 'wt' : 'at';
		$this->fp = @fopen($file, $mode);
	}

	private function closeFile(): void
	{
		if (!is_resource($this->fp))
		{
			return;
		}

		@fclose($this->fp);
	}
}