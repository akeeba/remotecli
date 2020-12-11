<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Download\Adapter;

use Akeeba\RemoteCLI\Download\DownloadInterface;
use Akeeba\RemoteCLI\Exception\CommunicationError;
use Akeeba\RemoteCLI\Utility\Uri;

/**
 * A download adapter using the cURL PHP integration
 */
class Curl extends AbstractAdapter implements DownloadInterface
{
	protected $headers = array();

	public function __construct()
	{
		$this->priority              = 110;
		$this->supportsFileSize      = true;
		$this->supportsChunkDownload = true;
		$this->name                  = 'curl';
		$this->isSupported           = function_exists('curl_init') && function_exists('curl_exec') && function_exists('curl_close');

		// PLEASE NOTE! In Phar packages, we MUST have a cacert on the filesystem, since the library can't load certificates
		// using the phar:// stream wrapper. This constant should ALWAYS be defined by the caller, this is just a fallback to avoid
		// things to break with non-https sites
		if (!defined('AKEEBA_CACERT_PEM'))
		{
			define('AKEEBA_CACERT_PEM', __DIR__ . '/cacert.pem');
		}
	}

	/** @inheritDoc */
	public function downloadAndReturn(string $url, ?string $from = null, ?string $to = null, array $params = [], $fp = null): string
	{
		$ch = curl_init();

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
			$to = $from;
			$from = $temp;
			unset($temp);
		}

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSLVERSION, 0);
        curl_setopt($ch, CURLOPT_CAINFO, AKEEBA_CACERT_PEM);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'reponseHeaderCallback'));

        if (!is_null($fp) && is_resource($fp))
        {
	        curl_setopt($ch, CURLOPT_FAILONERROR, true);
	        curl_setopt($ch, CURLOPT_HEADER, false);
	        curl_setopt($ch, CURLOPT_FILE, $fp);
        }

		if (!(empty($from) && empty($to)))
		{
			curl_setopt($ch, CURLOPT_RANGE, "$from-$to");
		}

        if (!empty($params))
        {
            foreach ($params as $k => $v)
            {
                @curl_setopt($ch, $k, $v);
            }
        }

		$result = curl_exec($ch);

		$errno  = curl_errno($ch);
		$errmsg = curl_error($ch);
        $error  = '';
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($result === false)
		{
			$error = sprintf('PHP cURL library error #%d with message ‘%s’', $errno, $errmsg);
		}
		elseif (($http_status >= 300) && ($http_status <= 399) && isset($this->headers['location']) && !empty($this->headers['location']))
		{
			return $this->downloadAndReturn($this->headers['location'], $from, $to, $params);
		}
		elseif ($http_status > 299)
		{
			$result = false;
			$errno = $http_status;
			$error = sprintf('Unexpected HTTP status %d', $http_status);
		}

		curl_close($ch);

		if ($result === false)
		{
			throw new CommunicationError($errno, $error);
		}

		return $result;
	}

	/** @inheritDoc */
	public function postAndReturn(string $url, string $data, string $contentType = 'application/x-www-form-urlencoded', array $params = []): string
	{
		$headers = [
			sprintf('Content-Type: %s', $contentType)
		];

		// Move the URI parameter _akeebaAuth to a header for POST requests
		$uri = new Uri($url);
		$akeebaAuth = $uri->getVar('_akeebaAuth');

		if (!is_null($akeebaAuth))
		{
			$headers[] = 'X-Akeeba-Auth: ' . $akeebaAuth;

			$uri->delVar('_akeebaAuth');
			$url = $uri->toString();
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSLVERSION, 0);

		curl_setopt($ch, CURLOPT_CAINFO, AKEEBA_CACERT_PEM);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'reponseHeaderCallback'));
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36');

		if (!empty($params))
		{
			foreach ($params as $k => $v)
			{
				@curl_setopt($ch, $k, $v);
			}
		}

		$result = curl_exec($ch);

		$errno       = curl_errno($ch);
		$errmsg      = curl_error($ch);
		$error       = '';
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($result === false)
		{
			$error = sprintf('PHP cURL library error #%d with message ‘%s’', $errno, $errmsg);
		}
		elseif (($http_status >= 300) && ($http_status <= 399) && isset($this->headers['location']) && !empty($this->headers['location']))
		{
			return $this->postAndReturn($this->headers['location'], $data, $contentType, $params);
		}
		elseif ($http_status > 299)
		{
			$result = false;
			$errno  = $http_status;
			$error  = sprintf('Unexpected HTTP status %d', $http_status);
		}

		curl_close($ch);

		if ($result === false)
		{
			throw new CommunicationError($errno, $error);
		}
		else
		{
			return $result;
		}
	}


	/**
	 * @inheritDoc
	 */
	public function getFileSize(string $url): int
	{
		$result = -1;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_NOBODY, true );
		curl_setopt($ch, CURLOPT_HEADER, true );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );

		$data = curl_exec($ch);
		curl_close($ch);

		if ($data)
		{
			$content_length = "unknown";
			$status = "unknown";
			$redirection = null;

			if (preg_match( "/^HTTP\/1\.[01] (\d\d\d)/i", $data, $matches))
			{
				$status = (int)$matches[1];
			}

			if (preg_match( "/Content-Length: (\d+)/i", $data, $matches))
			{
				$content_length = (int)$matches[1];
			}

			if (preg_match( "/Location: (.*)/i", $data, $matches))
			{
				$redirection = (int)$matches[1];
			}

			if( $status == 200 || ($status > 300 && $status <= 308) )
			{
				$result = $content_length;
			}

			if (($status > 300) && ($status <= 308))
			{
				if (!empty($redirection))
				{
					return $this->getFileSize($redirection);
				}

				return -1;
			}
		}

		return $result;
	}

	/**
	 * Handles the HTTP headers returned by cURL
	 *
	 * @param   resource  $ch    cURL resource handle (unused)
	 * @param   string    $data  Each header line, as returned by the server
	 *
	 * @return  int  The length of the $data string
	 */
	protected function reponseHeaderCallback($ch, string $data): int
	{
		$strlen = strlen($data);

		if (($strlen) <= 2)
		{
			return $strlen;
		}

		if (substr($data, 0, 4) == 'HTTP')
		{
			return $strlen;
		}

		if (strpos($data, ':') === false)
		{
			return $strlen;
		}

		[$header, $value] = explode(': ', trim($data), 2);

		$this->headers[strtolower($header)] = $value;

		return $strlen;
	}
}
