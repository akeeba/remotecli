<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteApi
{
	/** @var string The hostname to use */
	private $_host = '';
	/** @var The secret key to use */
	private $_secret = '';
	/** @var string The HTTP verb to use for communications */
	private $_verb = '';
	/** @var The format to use for communications */
	private $_format = '';

	/**
	 *
	 * @staticvar RemoteApi $instance
	 * @return RemoteApi
	 */
	public static function getInstance()
	{
		static $instance = null;

		if ( !is_object($instance))
		{
			$instance = new self();
		}

		return $instance;
	}

	public function isConfigured()
	{
		return ( !empty($this->_host) && !empty($this->_secret) && !empty($this->_verb) && !empty($this->_format));
	}

	public function doQuery($method, $params = array(), $component = 'com_akeeba')
	{
		$url = $this->getURL();
		$query = $this->prepareQuery($method, $params, $component);

		$result = $this->executeJSONQuery($url, $query);
		$result->body->data = json_decode($result->body->data);

		if (is_null($result->body->data))
		{
			throw new RemoteApiExceptionBody;
		}

		return $result;
	}

	public function executeJSONQuery($url, $query)
	{
		$options = RemoteUtilsCli::getInstance();
		$verbose = $options->verbose;

		if ($this->_verb == 'GET')
		{
			$url .= '?' . $query;
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		if ($this->_verb == 'POST')
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
		}

		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		//curl_setopt($ch, CURLOPT_TIMEOUT, 180);

		// Pretend we are IE7, so that webservers play nice with us
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)');

		if ($verbose)
		{
			RemoteUtilsRender::debug('URL: ' . $url);
		}

		$raw = curl_exec($ch);
		curl_close($ch);

		if ($raw === false)
		{
			if ($verbose)
			{
				RemoteUtilsRender::debug('cURL error');
			}

			// throw new RemoteApiExceptionComms($curlErrorMessage, $curlErrorNumber);
			throw new RemoteApiExceptionComms;
		}

		if ($verbose)
		{
			RemoteUtilsRender::debug('Raw Result: ' . $raw);
		}

		$startPos = strpos($raw, '###') + 3;
		$endPos   = strrpos($raw, '###');

		if (($startPos !== false) && ($endPos !== false))
		{
			$json = substr($raw, $startPos, $endPos - $startPos);
		}
		else
		{
			$json = $raw;
		}

		$result = json_decode($json, false);

		if (is_null($result))
		{
			$options = RemoteUtilsCli::getInstance();

			if ($options->verbose)
			{
				RemoteUtilsRender::debug('Invalid JSON: ' . $json);
			}

			throw new RemoteApiExceptionJson;
		}

		if ($verbose)
		{
			RemoteUtilsRender::debug('Parsed Result: ' . print_r($result, true));
		}

		return $result;
	}

	public function getURL()
	{
		return $this->_host . '/index.php';
	}

	public function prepareQuery($method, $params, $component = 'com_akeeba')
	{
		$body = array(
			'method' => $method,
			'data'   => (object)$params
		);

		$salt              = md5(microtime(true));
		$challenge         = $salt . ':' . md5($salt . $this->_secret);
		$body['challenge'] = $challenge;
		$bodyData          = json_encode($body);

		$query = 'option=' . $component . '&view=json&json=' . urlencode(json_encode(array(
				'encapsulation' => 1,
				'body'          => $bodyData
			)));

		if (empty($this->_format))
		{
			$this->_format = 'html';
		}
		$query .= '&format=' . $this->_format;
		if ($this->_format == 'html')
		{
			$query .= '&tmpl=component';
		}

		return $query;
	}

	private function _setHost($host)
	{
		if ((strpos($host, 'http://') !== 0) && (strpos($host, 'https://') !== 0))
		{
			$host = 'http://' . $host;
		}
		$host = rtrim($host, '/');

		$this->_host = $host;
	}

	private function _getHost()
	{
		return $this->_host;
	}

	private function _setSecret($secret)
	{
		$this->_secret = $secret;
	}

	private function _getSecret()
	{
		return $this->_secret;
	}

	private function _setVerb($verb)
	{
		$verb = strtoupper($verb);

		if ( !in_array($verb, array('GET', 'POST')))
		{
			$verb = 'GET';
		}

		$this->_verb = $verb;
	}

	private function _getVerb()
	{
		return $this->_verb;
	}

	private function _setFormat($format)
	{
		$format = strtolower($format);

		if ( !in_array($format, array('raw', 'html')))
		{
			$format = 'raw';
		}

		$this->_format = $format;
	}

	private function _getFormat()
	{
		return $this->_format;
	}

	public function __get($name)
	{
		$method = '_get' . ucfirst($name);
		if (method_exists($this, $method))
		{
			return $this->$method();
		}
		else
		{
			user_error("Unknown property $name in " . __CLASS__, E_WARNING);
		}
	}

	public function __set($name, $value)
	{
		$method = '_set' . ucfirst($name);
		if (method_exists($this, $method))
		{
			$this->$method($value);
		}
		else
		{
			user_error("Unknown property $name in " . __CLASS__, E_WARNING);
		}
	}

}