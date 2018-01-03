<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright Copyright (c)2008-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Download\Adapter;

use Akeeba\RemoteCLI\Download\DownloadInterface;
use Akeeba\RemoteCLI\Exception\CommunicationError;

/**
 * A download adapter using the cURL PHP integration
 */
class Curl extends AbstractAdapter implements DownloadInterface
{
	public function __construct()
	{
		$this->priority              = 110;
		$this->supportsFileSize      = true;
		$this->supportsChunkDownload = true;
		$this->name                  = 'curl';
		$this->isSupported           = function_exists('curl_init') && function_exists('curl_exec') && function_exists('curl_close');
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
	 * @param   string    $url     The remote file's URL
	 * @param   integer   $from    Byte range to start downloading from. Use null for start of file.
	 * @param   integer   $to      Byte range to stop downloading. Use null to download the entire file ($from is ignored)
     * @param   array     $params  Additional params that will be added before performing the download
	 * @param   resource  $fp      A file pointer to download to. If provided, the method returns null.
	 *
	 * @return  string  The raw file data retrieved from the remote URL.
	 *
	 * @throws  CommunicationError  When there is an error communicating with the server
	 */
	public function downloadAndReturn($url, $from = null, $to = null, array $params = array(), $fp = null)
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
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');

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
		$ch = curl_init();


		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			sprintf('Content-Type: %s', $contentType)
		]);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSLVERSION, 0);
		curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');

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
	 * Get the size of a remote file in bytes
	 *
	 * @param   string  $url  The remote file's URL
	 *
	 * @return  integer  The file size, or -1 if the remote server doesn't support this feature
	 */
	public function getFileSize($url)
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

			if (preg_match( "/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches))
			{
				$status = (int)$matches[1];
			}

			if (preg_match( "/Content-Length: (\d+)/", $data, $matches))
			{
				$content_length = (int)$matches[1];
			}

			if( $status == 200 || ($status > 300 && $status <= 308) )
			{
				$result = $content_length;
			}
		}

		return $result;
	}
}
