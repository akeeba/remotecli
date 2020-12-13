<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
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

	/** @inheritDoc */
	public function downloadAndReturn(string $url, ?string $from = null, ?string $to = null, array $params = [], $fp = null): string
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

		$length  = null;
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

		if (!(empty($from) && empty($to)))
		{
			$length                    = $from - $to + 1;
			$options['http']['header'] = "Range: bytes=$from-$to\r\n";
		}

		$options = array_merge_recursive($options, $params);
		$context = stream_context_create($options);

		// Write directly to file?
		if (!is_null($fp) && is_resource($fp))
		{
			// Open the remote URL with URL fopen() wrappers.
			$remoteFP = fopen($url, 'rb', null, $context);

			// If that failed, go through our standard failure detection code and raise the relevant exception
			if ($remoteFP === false)
			{
				return $this->evaluateHTTPResponse(false, null);
			}

			// If we are supposed to read a specific chunk we need to seek to the offset
			if (!is_null($length))
			{
				fseek($remoteFP, $from, SEEK_CUR);
			}

			$read = 0;

			while (!feof($remoteFP))
			{
				$chunkSize = 1048576;

				if (!is_null($length))
				{
					$chunkSize = min($chunkSize, $length - $read);
				}

				if ($chunkSize <= 0)
				{
					break;
				}

				$chunk = fread($remoteFP, $chunkSize);
				$read += strlen($chunk);

				fwrite($fp, $chunk);
			}

			fclose($remoteFP);

			$result = null;
		}
		else
		{
			$result  = @file_get_contents($url, false, $context, $from, $length);
		}

		if (!isset($http_response_header))
		{
			$http_response_header = null;
		}

		return $this->evaluateHTTPResponse($result, $http_response_header);
	}

	/** @inheritDoc */
	public function postAndReturn(string $url, string $data, string $contentType = 'application/x-www-form-urlencoded', array $params = []): string
	{
		$options = array(
			'http' => array(
				'user_agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36',
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

		$options = array_merge_recursive($options, $params);

		$context = stream_context_create($options);
		$result  = @file_get_contents($url, false, $context);

		if (!isset($http_response_header))
		{
			$http_response_header = null;
		}

		return $this->evaluateHTTPResponse($result, $http_response_header);
	}

	/**
	 * Evaluate the server response, including the HTTP status, and the response itself. If an error has occurred we
	 * throw a CommunicationError exception, otherwise we return the raw response content.
	 *
	 * @param   string|bool  $result  The raw response content
	 * @param   string|null  $http_response_header
	 *
	 * @return  string  The raw response content
	 */
	private function evaluateHTTPResponse($result, ?string $http_response_header): string
	{
		global $http_response_header_test;

		if (is_null($http_response_header) && empty($http_response_header_test))
		{
			$error = 'Could not open the download URL using URL fopen() wrappers.';

			throw new CommunicationError(61, $error);
		}

		// Used for testing
		if (is_null($http_response_header) && !empty($http_response_header_test))
		{
			$http_response_header = $http_response_header_test;
		}

		$http_code = 200;
		$nLines    = is_array($http_response_header) || $http_response_header instanceof \Countable ? count($http_response_header) : 0;

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
