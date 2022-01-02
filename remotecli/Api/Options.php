<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Api;

use Akeeba\RemoteCLI\Utility\Uri;


/**
 * Immutable options for the API
 *
 * @property-read   string $host           Protocol, hostname and path to the endpoint
 * @property-read   string $secret         Secret key to use in communications (used for authentication)
 * @property-read   string $endpoint       Endpoint file, defaults to index.php. Using remote.php clears format and
 *                  component.
 * @property-read   string $component      Component used in Joomla! sites, defaults to com_akeeba
 * @property-read   string $verb           HTTP verb to use in the API< defaults to GET
 * @property-read   string $format         Format used for Joomla! sites, defaults to html
 * @property-read   string $ua             User Agent string to use
 * @property-read   string $capath         Certificate Authority cache path
 * @property-read   bool   $verbose        Should I be verbose about what I'm doing?
 * @property-read   int    $encapsulation  The API encapsulation, defaults to AES-128 CBC
 * @property-read   bool   $legacy         Use legacy, unsafe AES CBC encryption (for old versions of Akeeba Backup /
 *                  Solo)
 * @property-read   string $view           View name. 'json' is the v1 JSON API. 'api' is the v2 JSON API.
 */
class Options
{
	public const ENC_NONE = 0;

	private $capath = null;

	private $component = null;

	private $endpoint = 'index.php';

	private $format = '';

	private $host;

	private $legacy = false;

	private $secret;

	private $ua = '';

	private $verb = 'GET';

	private $verbose = false;

	private $view = 'api';

	/**
	 * OutputOptions constructor. The options you pass initialize the immutable object.
	 *
	 * @param   array  $options  The options to initialize the object with
	 * @param   bool   $strict   When enabled, unknown $options keys will throw an exception instead of silently
	 *                           skipped.
	 */
	public function __construct(array $options, $strict = false)
	{
		$this->ua = 'AkeebaRemoteCLI/' . ARCCLI_VERSION;

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
			$this->format    = '';
			$this->component = '';
		}

		// Make sure I have a valid CA cache path
		if (empty($this->capath))
		{
			$this->capath = AKEEBA_CACERT_PEM;
		}
	}

	/**
	 * Magic getter, used to implement read only properties.
	 *
	 * @param   string  $name  The name of the property bneing read
	 *
	 * @return  mixed
	 */
	public function __get(string $name)
	{
		if (property_exists($this, $name))
		{
			return $this->$name;
		}

		throw new \LogicException(sprintf('Class %s does not have property ‘%s’', __CLASS__, $name));
	}

	/**
	 * Gets an exact copy of the object with the new options overriding the current ones
	 *
	 * @param   array  $options  The options you are overriding
	 *
	 * @return  self
	 */
	public function getModifiedClone(array $options): Options
	{
		$currentOptions = [];

		foreach ($this as $k => $v)
		{
			$currentOptions[$k] = $v;
		}

		$options = array_replace_recursive($currentOptions, $options);

		return new self($options);
	}

	/**
	 * Normalize the host. Make sure there is an HTTP or HTTPS scheme. Also extract the endpoint if it's specified.
	 *
	 * @return  void  Operates directly to the host and endpoint properties of this object.
	 */
	private function parseHost(): void
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

		$component = $uri->getVar('option', '');

		if (!empty($component))
		{
			$this->component = $component;
		}

		$format = $uri->getVar('format', '');

		if (!empty($format))
		{
			$this->format = $format;
		}

		$view = $uri->getVar('view', '');

		if (!empty($view))
		{
			$this->view = $view;
		}

		$originalPath = $uri->getPath();
		[$path, $endpoint] = $this->parsePath($originalPath);

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
	 * @param   string|null  $originalPath  The original UTL path
	 *
	 * @return  array  [$path, $endpoint]. The endpoint may be empty.
	 */
	private function parsePath(?string $originalPath): array
	{
		$originalPath = trim($originalPath ?? '', "/");

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
}
