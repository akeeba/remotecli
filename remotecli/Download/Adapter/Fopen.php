<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Download\Adapter;

use Akeeba\RemoteCLI\Download\DownloadInterface;
use Akeeba\RemoteCLI\Exception\CommunicationError;

/**
 * A download adapter using URL fopen() wrappers
 */
class Fopen extends AbstractAdapter implements DownloadInterface
{
	public function __construct()
	{
		$this->priority = 100;
		$this->supportsFileSize = false;
		$this->supportsChunkDownload = true;
		$this->name = 'fopen';

		// If we are not allowed to use ini_get, we assume that URL fopen is
		// disabled.
		if (!function_exists('ini_get'))
		{
			$this->isSupported = false;
		}
		else
		{
			$this->isSupported = ini_get('allow_url_fopen');
		}
	}

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
	 * @param   string   $url   The remote file's URL
	 * @param   integer  $from  Byte range to start downloading from. Use null for start of file.
	 * @param   integer  $to    Byte range to stop downloading. Use null to download the entire file ($from is ignored)
     * @param   array    $params  Additional params that will be added before performing the download
	 *
	 * @return  string  The raw file data retrieved from the remote URL.
	 *
	 * @throws  CommunicationError  When there is an error communicating with the server
	 */
	public function downloadAndReturn($url, $from = null, $to = null, array $params = array())
	{
		if (empty($from))
		{
			$from = 0;
		}

		if (empty($to))
		{
			$to = 0;
		}

		if ($to < $from)
		{
			$temp = $to;
			$to   = $from;
			$from = $temp;
			unset($temp);
		}


		if (!(empty($from) && empty($to)))
		{
			$options = array(
				'http' => array(
					'method'          => 'GET',
					'header'          => "Range: bytes=$from-$to\r\n",
					'follow-location' => 1,
				),
				'ssl'  => array(
					'verify_peer'  => true,
					'cafile'       => __DIR__ . '/cacert.pem',
					'verify_depth' => 5,
				),
			);

			$options = array_merge($options, $params);

			$context = stream_context_create($options);
			$result  = @file_get_contents($url, false, $context, $from - $to + 1);
		}
		else
		{
			$options = array(
				'http' => array(
					'method'          => 'GET',
					'follow-location' => 1,
				),
				'ssl'  => array(
					'verify_peer'  => true,
					'cafile'       => __DIR__ . '/cacert.pem',
					'verify_depth' => 5,
				),
			);

			$options = array_merge($options, $params);

			$context = stream_context_create($options);
			$result  = @file_get_contents($url, false, $context);
		}

		return $this->evaluateHTTPResponse($result);
	}

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
	public function postAndReturn($url, $data, $contentType = 'application/x-www-form-urlencoded', array $params = array())
	{
		$options = array(
			'http' => array(
				'method'          => 'POST',
				'header'          => sprintf("Content-type: %s\r\n", $contentType),
				'content'         => $data,
				'follow-location' => 1,
			),
			'ssl'  => array(
				'verify_peer'  => true,
				'cafile'       => __DIR__ . '/cacert.pem',
				'verify_depth' => 5,
			),
		);

		$options = array_merge($options, $params);

		$context = stream_context_create($options);
		$result  = @file_get_contents($url, false, $context);

		return $this->evaluateHTTPResponse($result);
	}

	/**
	 * Evaluate the server response, including the HTTP status, and the response itself. If an error has occurred we
	 * throw a CommunicationError exception, otherwise we return the raw response content.
	 *
	 * @param   string  $result  The raw response content
	 *
	 * @return  string  The raw response content
	 *
	 * @throws  CommunicationError  In case a communications error has been detected
	 */
	private function evaluateHTTPResponse($result)
	{
		global $http_response_header_test;

		if (!isset($http_response_header) && empty($http_response_header_test))
		{
			$error = 'Could not open the download URL using URL fopen() wrappers.';

			throw new CommunicationError(61, $error);
		}

		// Used for testing
		if (!isset($http_response_header) && !empty($http_response_header_test))
		{
			$http_response_header = $http_response_header_test;
		}

		$http_code = 200;
		$nLines    = count($http_response_header);

		for ($i = $nLines - 1; $i >= 0; $i--)
		{
			$line = $http_response_header[$i];

			if (strncasecmp("HTTP", $line, 4) == 0)
			{
				$response  = explode(' ', $line);
				$http_code = $response[1];

				break;
			}
		}

		if ($http_code >= 299)
		{
			$error = sprintf('Unexpected HTTP status %d', $http_code);

			throw new CommunicationError($http_code, $error);
		}

		if ($result === false)
		{
			$error = sprintf('Could not open the download URL using URL fopen() wrappers.');

			throw new CommunicationError(62, $error);
		}

		return $result;
	}
}