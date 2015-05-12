<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteApi
{
	/** @var  string  The hostname to use */
	private $_host = '';

	/** @var  string  The secret key to use */
	private $_secret = '';

	/** @var  string  The HTTP verb to use for communications */
	private $_verb = '';

	/** @var  string  The format to use for communications */
	private $_format = '';

	/**
	 * Gets a Singleton object instance of this class
	 *
	 * @staticvar  RemoteApi  $instance
	 *
	 * @return  RemoteApi
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

	/**
	 * Do I have sufficient configuration information to proceed?
	 *
	 * @return  bool
	 */
	public function isConfigured()
	{
		return ( !empty($this->_host) && !empty($this->_secret) && !empty($this->_verb) && !empty($this->_format));
	}

	/**
	 * Execute a JSON API call and return the parsed result
	 *
	 * @param   string  $method     API method to call
	 * @param   array   $params     API call's parameters
	 * @param   string  $component  Component name (default: com_akeeba)
	 *
	 * @return mixed
	 * @throws RemoteApiExceptionBody
	 * @throws RemoteApiExceptionComms
	 * @throws RemoteApiExceptionJson
	 */
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

	/**
	 * Internal method to execute the actual web call
	 *
	 * @param   string  $url    The endpoint URL
	 * @param   string  $query  The query to send to the endpoint
	 *
	 * @return  stdClass
	 *
	 * @throws  RemoteApiExceptionComms
	 * @throws  RemoteApiExceptionJson
	 */
	protected function executeJSONQuery($url, $query)
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

		$startPos = strpos($raw, '###') + 3;
		$endPos   = strrpos($raw, '###');

		$json = $raw;

		if (($startPos !== false) && ($endPos !== false))
		{
			$json = substr($raw, $startPos, $endPos - $startPos);
		}

		if ($verbose)
		{
			RemoteUtilsRender::debug('Raw Response: ' . $json);
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

		$encrypt = new RemoteUtilsEncrypt();

		switch ($result->encapsulation)
		{
			case 2:
				$result->body->data = $encrypt->AESDecryptCtr($result->body->data, $this->_secret, 128);
				break;

			case 3:
				$result->body->data = $encrypt->AESDecryptCtr($result->body->data, $this->_secret, 256);
				break;

			case 4:
				$result->body->data = base64_decode($result->body->data);
				$result->body->data = $encrypt->AESDecryptCBC($result->body->data, $this->_secret, 128);
				$result->body->data = rtrim($result->body->data, chr(0));
				break;

			case 5:
				$result->body->data = base64_decode($result->body->data);
				$result->body->data = $encrypt->AESDecryptCBC($result->body->data, $this->_secret, 256);
				$result->body->data = rtrim($result->body->data, chr(0));
				break;
		}

		if ($verbose)
		{
			RemoteUtilsRender::debug('Parsed Result: ' . print_r($result, true));
		}

		return $result;
	}

	/**
	 * Get the endpoint URL
	 *
	 * @return  string
	 */
	public function getURL()
	{
		return $this->_host . '/index.php';
	}

	/**
	 * Prepare the query string for a JSON API call
	 *
	 * @param   string  $method     API method to call
	 * @param   array   $params     API call's parameters
	 * @param   string  $component  Component name (default: com_akeeba)
	 *
	 * @return string
	 */
	public function prepareQuery($method, $params, $component = 'com_akeeba')
	{
		$encapsulation = $this->getEncapsulation();

		$body = array(
			'method' => $method,
			'data'   => (object)$params
		);

		if ($encapsulation == 1)
		{
			$salt              = md5(microtime(true));
			$challenge         = $salt . ':' . md5($salt . $this->_secret);
			$body['challenge'] = $challenge;
		}

		$bodyData          = json_encode($body);

		$jsonSource = array(
			'encapsulation' => $encapsulation,
			'body' => $bodyData
		);

		$encrypt = new RemoteUtilsEncrypt();

		switch ($encapsulation)
		{
			case 2: // AES CTR 128
				$jsonSource['body'] = $encrypt->AESEncryptCtr($jsonSource['body'], $this->_secret, 128);
				break;

			case 3: // AES CTR 256
				$jsonSource['body'] = $encrypt->AESEncryptCtr($jsonSource['body'], $this->_secret, 256);
				break;

			case 4: // AES CBC 128
				$jsonSource['body'] = $encrypt->AESEncryptCBC($jsonSource['body'], $this->_secret, 128);
				$jsonSource['body'] = base64_encode($jsonSource['body']);
				break;

			case 5: // AES CBC 256
				$jsonSource['body'] = $encrypt->AESEncryptCBC($jsonSource['body'], $this->_secret, 256);
				$jsonSource['body'] = base64_encode($jsonSource['body']);
				break;
		}

		$json = json_encode($jsonSource);


		$query = 'option=' . $component . '&view=json&json=' . urlencode($json);

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

	/**
	 * Get the encapsulation specified in the command line. If an AES CBC encapsulation is not supported it will be
	 * downgraded to AES CTR.
	 *
	 * @return  int
	 */
	private function getEncapsulation()
	{
		$options = RemoteUtilsCli::getInstance();

		$ret = $options->get('encapsulation', 1);

		if (!is_numeric($ret))
		{
			$ret = strtoupper($ret);

			switch ($ret)
			{
				case 'CTR128':
					$ret = 2;
					break;

				case 'CTR256':
					$ret = 3;
					break;

				case 'AES128':
					$ret = 4;
					break;

				case 'AES256':
					$ret = 5;
					break;

				default:
					$ret = 1;
					break;
			}
		}

		// Check availability of AES CBC encryption and downgrade to CTR when necessary
		$hasMCrypt = function_exists('mcrypt_module_open') && function_exists('mcrypt_generic_init') &&
			function_exists('mcrypt_generic') && function_exists('mcrypt_generic_deinit') &&
			function_exists('mdecrypt_generic') &&  function_exists('mcrypt_list_algorithms');

		if ($hasMCrypt)
		{
			$algos = mcrypt_list_algorithms();

			$hasMCrypt = in_array('rijndael-128', $algos);
		}

		// If AES CBC is used but it's not supported downgrade to CTR
		if (($ret >= 4) && !$hasMCrypt)
		{
			$ret -= 2;
		}

		return $ret;
	}

	/**
	 * Setter for hostname
	 *
	 * @param   string  $host
	 */
	private function _setHost($host)
	{
		if ((strpos($host, 'http://') !== 0) && (strpos($host, 'https://') !== 0))
		{
			$host = 'http://' . $host;
		}

		$host = rtrim($host, '/');

		$this->_host = $host;
	}

	/**
	 * Getter for hostname
	 *
	 * @return  string
	 */
	private function _getHost()
	{
		return $this->_host;
	}

	/**
	 * Setter for the site's secret key
	 *
	 * @param   string  $secret
	 */
	private function _setSecret($secret)
	{
		$this->_secret = $secret;
	}

	/**
	 * Getter for the site's secret key
	 *
	 * @return   string
	 */
	private function _getSecret()
	{
		return $this->_secret;
	}

	/**
	 * Setter for HTTP verb
	 *
	 * @param   string  $verb
	 */
	private function _setVerb($verb)
	{
		$verb = strtoupper($verb);

		if ( !in_array($verb, array('GET', 'POST')))
		{
			$verb = 'GET';
		}

		$this->_verb = $verb;
	}

	/**
	 * Getter for HTTP verb
	 *
	 * @return  string
	 */
	private function _getVerb()
	{
		return $this->_verb;
	}

	/**
	 * Setter for the format query string
	 *
	 * @param   string  $format
	 */
	private function _setFormat($format)
	{
		$format = strtolower($format);

		if ( !in_array($format, array('raw', 'html')))
		{
			$format = 'raw';
		}

		$this->_format = $format;
	}

	/**
	 * Getter for the format query string
	 *
	 * @return  string
	 */
	private function _getFormat()
	{
		return $this->_format;
	}

	/**
	 * Magic getter for private properties
	 *
	 * @param   string  $name  The name of the property to return
	 *
	 * @return  mixed
	 */
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

	/**
	 * Magic setter for private properties
	 *
	 * @param   string  $name   The name of the property to set
	 * @param   mixed   $value  The value to set it to
	 */
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