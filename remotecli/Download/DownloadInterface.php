<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Download;

/**
 * Interface DownloadInterface
 *
 * @package Awf\Download
 *
 * @codeCoverageIgnore
 */
interface DownloadInterface
{
	/**
	 * Does this download adapter support downloading files in chunks?
	 *
	 * @return  boolean  True if chunk download is supported
	 */
	public function supportsChunkDownload(): bool;

	/**
	 * Does this download adapter support reading the size of a remote file?
	 *
	 * @return  boolean  True if remote file size determination is supported
	 */
	public function supportsFileSize(): bool;

	/**
	 * Is this download class supported in the current server environment?
	 *
	 * @return  boolean  True if this server environment supports this download class
	 */
	public function isSupported(): bool;

	/**
	 * Get the priority of this adapter. If multiple download adapters are
	 * supported on a site, the one with the highest priority will be
	 * used.
	 *
	 * @return  int
	 */
	public function getPriority(): int;

	/**
	 * Returns the name of this download adapter in use
	 *
	 * @return  string
	 */
	public function getName(): string;

	/**
	 * Download a part (or the whole) of a remote URL and return the downloaded
	 * data. You are supposed to check the size of the returned data. If it's
	 * smaller than what you expected you've reached end of file. If it's empty
	 * you have tried reading past EOF. If it's larger than what you expected
	 * the server doesn't support chunk downloads.
	 *
	 * If this class' supportsChunkDownload returns false you should assume
	 * that the $from and $to parameters will be ignored.
	 *
	 * @param   string         $url     The remote file's URL
	 * @param   string|null    $from    Byte range to start downloading from. Use null for start of file.
	 * @param   string|null    $to      Byte range to stop downloading. Use null to download the entire file ($from is
	 *                                  ignored)
	 * @param   array          $params  Additional params that will be added before performing the download
	 * @param   resource|null  $fp      A file pointer to download to. If provided, the method returns null.
	 *
	 * @return  string  The raw file data retrieved from the remote URL.
	 *
	 * @throws \Exception A generic exception is thrown on error
	 */
	public function downloadAndReturn(string $url, ?string $from = null, ?string $to = null, array $params = [], $fp = null): string;

	/**
	 * Send data to the server using a POST request and return the server response.
	 *
	 * @param   string  $url          The URL to send the data to.
	 * @param   string  $data         The data to send to the server. If they need to be URL-encoded you have to do it
	 *                                yourself.
	 * @param   string  $contentType  The type of the form data. The default is application/x-www-form-urlencoded.
	 * @param   array   $params       Additional params that will be added before performing the download
	 *
	 * @return  string  The raw response
	 */
	public function postAndReturn(string $url, string $data, string $contentType = 'application/x-www-form-urlencoded', array $params = []): string;

	/**
	 * Get the size of a remote file in bytes
	 *
	 * @param   string  $url  The remote file's URL
	 *
	 * @return  int  The file size, or -1 if the remote server doesn't support this feature
	 */
	public function getFileSize(string $url): int;
}
