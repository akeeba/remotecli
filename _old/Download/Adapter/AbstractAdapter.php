<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\OLD\RemoteCLI\Download\Adapter;

use Akeeba\OLD\RemoteCLI\Download\DownloadInterface;

abstract class AbstractAdapter implements DownloadInterface
{
    /**
     * Load order priority
     *
     * @var  int
     */
	public $priority = 100;

    /**
     * Name of the adapter (identical to filename)
     *
     * @var  string
     */
	public $name = '';

    /**
     * Is this adapter supported in the current execution environment?
     *
     * @var  bool
     */
	public $isSupported = false;

    /**
     * Does this adapter support chunked downloads?
     *
     * @var  bool
     */
	public $supportsChunkDownload = false;

    /**
     * Does this adapter support querying the remote file's size?
     *
     * @var  bool
     */
	public $supportsFileSize = false;

	/**
	 * Does this download adapter support downloading files in chunks?
	 *
	 * @return  boolean  True if chunk download is supported
	 */
	public function supportsChunkDownload(): bool
	{
		return $this->supportsChunkDownload;
	}

	/**
	 * Does this download adapter support reading the size of a remote file?
	 *
	 * @return  boolean  True if remote file size determination is supported
	 */
	public function supportsFileSize(): bool
	{
		return $this->supportsFileSize;
	}

	/**
	 * Is this download class supported in the current server environment?
	 *
	 * @return  boolean  True if this server environment supports this download class
	 */
	public function isSupported(): bool
	{
		return $this->isSupported;
	}

	/**
	 * Get the priority of this adapter. If multiple download adapters are
	 * supported on a site, the one with the highest priority will be
	 * used.
	 *
	 * @return  int
	 */
	public function getPriority(): int
	{
		return $this->priority;
	}

	/**
	 * Returns the name of this download adapter in use
	 *
	 * @return  string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/** @inheritDoc */
	public function downloadAndReturn(string $url, ?string $from = null, ?string $to = null, array $params = [], $fp = null): string
	{
		return '';
	}

	/** @inheritDoc */
	public function getFileSize(string $url): int
	{
		return -1;
	}
}
