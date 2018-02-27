<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright Copyright (c)2008-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Api;
use Akeeba\RemoteCLI\Utility\Uri;


/**
 * Immutable options for the API
 *
 * @property-read   string  $host           Protocol, hostname and path to the endpoint
 * @property-read   string  $secret         Secret key to use in communications (used for authentication)
 * @property-read   string  $endpoint       Endpoint file, defaults to index.php. Using remote.php clears format and component.
 * @property-read   string  $component      Component used in Joomla! sites, defaults to com_akeeba
 * @property-read   string  $verb           HTTP verb to use in the API< defaults to GET
 * @property-read   string  $format         Format used for Joomla! sites, defaults to html
 * @property-read   string  $ua             User Agent string to use
 * @property-read   string  $capath         Certificate Authority cache path
 * @property-read   bool    $verbose        Should I be verbose about what I'm doing?
 * @property-read   int     $encapsulation  The API encapsulation, defaults to AES-128 CBC
 * @property-read   bool    $legacy         Use legacy, unsafe AES CBC encryption (for old versions of Akeeba Backup / Solo)
 */
class Options
{
	const ENC_RAW = 1;
	const ENC_CTR128 = 2;
	const ENC_CTR256 = 3;
	const ENC_CBC128 = 4;
	const ENC_CBC256 = 5;

	private $host;
	private $secret;
	private $endpoint = 'index.php';
	private $component = 'com_akeeba';
	private $verb = 'GET';
	private $format = 'html';
	private $ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';
	private $verbose = false;
	private $encapsulation = self::ENC_CBC128;
	private $legacy = false;
	private $capath = null;

	/**
	 * OutputOptions constructor. The options you pass initialize the immutable object.
	 *
	 * @param   array   $options  The options to initialize the object with
	 * @param   bool    $strict   When enabled, unknown $options keys will throw an exception instead of silently skipped.
	 */
	public function __construct(array $options, $strict = false)
	{
		foreach ($options as $k => $v)
		{
			if (!property_exists($this, $k))
			{
				if ($strict)
				{
					throw new \LogicException(sprintf('Class %s does not have property ‘%s’', __CLASS__, $k));
				}

				continue;
			}

			$this->$k = $v;
		}

		// Normalize the host definition
		$this->parseHost();

		// Akeeba Solo or Akeeba Backup for WordPress endpoint; do not use format and component parameters in the URL
		if ($this->endpoint == 'remote.php')
		{
			$this->format = '';
			$this->component = '';
		}

		// Make sure I have a valid encapsulation
		if (is_string($this->encapsulation))
		{
			switch (strtoupper($this->encapsulation))
			{
				case 'RAW':
					$this->encapsulation = self::ENC_RAW;
					break;

				case 'CTR128':
					$this->encapsulation = self::ENC_CTR128;
					break;

				case 'CTR256':
					$this->encapsulation = self::ENC_CTR256;
					break;

				default:
				case 'CBC128':
					$this->encapsulation = self::ENC_CBC128;
					break;

				case 'CBC256':
					$this->encapsulation = self::ENC_CBC256;
					break;
			}
		}

		// Make sure I have a valid CA cache path
		if (empty($this->capath))
		{
			$this->capath = __DIR__ . '/../Download/Adapter/cacert.pem';
		}
	}

	/**
	 * Magic getter, used to implement read only properties.
	 *
	 * @param   string  $name  The name of the property bneing read
	 *
	 * @return  mixed
	 */
	public function __get($name)
	{
		if (property_exists($this, $name))
		{
			return $this->$name;
		}

		throw new \LogicException(sprintf('Class %s does not have property ‘%s’', __CLASS__, $name));
	}

	/**
	 * Normalize the host. Make sure there is an HTTP or HTTPS scheme. Also extract the endpoint if it's specified.
	 *
	 * @return  void  Operates directly to the host and endpoint properties of this object.
	 */
	private function parseHost()
	{
		if (empty($this->host))
		{
			return;
		}

		$uri = new Uri($this->host);

		if (!in_array($uri->getScheme(), ['http', 'https']))
		{
			$uri->setScheme('http');
		}

		$originalPath = $uri->getPath();
		list ($path, $endpoint) = $this->parsePath($originalPath);

		$uri->setPath('/' . ltrim($path));

		if (!empty($endpoint) && (substr($endpoint, -4) == '.php'))
		{
			$this->endpoint = $endpoint;
		}

		$this->host = $uri->toString(['scheme', 'user', 'pass', 'host', 'port', 'path']);
	}

	/**
	 * Parse the path of a URL and either extract a .php endpoint or strip a misplaced index.html or other useless bit.
	 *
	 * @param   string  $originalPath  The original UTL path
	 *
	 * @return  array  [$path, $endpoint]. The endpoint may be empty.
	 */
	private function parsePath($originalPath)
	{
		$originalPath = trim($originalPath, "/");

		// The path is "/"
		if (empty($originalPath))
		{
			return ['', ''];
		}

		$lastSlashPost = strrpos($originalPath, '/');

		// Normally should not happen since I've stripped the slashes.
		if ($lastSlashPost === 0)
		{
			throw new \LogicException("I found a misplaced slash in a path. Notify the developer. This must never happen.");
		}

		$endpoint = $originalPath;
		$path     = '';

		if ($lastSlashPost !== false)
		{
			$endpoint = substr($originalPath, $lastSlashPost + 1);
			$path     = substr($originalPath, 0, $lastSlashPost);
		}

		// The path is "some/thing/or/another"
		if (strpos($endpoint, '.') === false)
		{
			return [$originalPath, ''];
		}

		// The path was "some/thing/whatever.ext". If .ext is .php I have an endpoint. Otherwise I will strip it.
		if (substr($endpoint, -4) == '.php')
		{
			return [$path, $endpoint];
		}

		return [$path, ''];
	}

	/**
	 * Gets an exact copy of the object with the new options overriding the current ones
	 *
	 * @param   array  $options  The options you are overriding
	 *
	 * @return  self
	 */
	public function getModifiedClone($options)
	{
		$currentOptions = [];

		foreach ($this as $k => $v)
		{
			$currentOptions[$k] = $v;
		}

		$options = array_replace_recursive($currentOptions, $options);

		return new self($options);
	}
}
