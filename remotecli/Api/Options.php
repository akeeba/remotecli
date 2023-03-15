<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api;

use Akeeba\RemoteCLI\Api\Exception\NoConfiguredHost;
use Akeeba\RemoteCLI\Api\Exception\NoConfiguredSecret;
use Composer\CaBundle\CaBundle;
use Joomla\Uri\Uri;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Immutable options for the Akeeba Backup JSON API Connector
 *
 * @property-read   string               $host           Protocol, hostname and path to the endpoint
 * @property-read   string               $secret         Secret key to use in communications (used for authentication)
 * @property-read   string               $endpoint       Endpoint file, e.g. index.php.
 * @property-read   string               $component      Component used in Joomla! sites, defaults to com_akeeba
 * @property-read   string               $verb           HTTP verb to use in the API, default: GET
 * @property-read   string               $format         Format used for Joomla! sites, default: html
 * @property-read   string               $ua             User Agent string
 * @property-read   string               $capath         Certificate Authority cache path
 * @property-read   bool                 $verbose        Enable verbose (debug) mode.
 * @property-read   string               $view           View name. 'json' is v1 API, 'api' is v2 API.
 * @property-read   bool                 $isWordPress    Is this a WordPress site using admin-ajax.php as entry point?
 * @property-read   LoggerInterface|null $logger         PSR-3 compatible logger
 */
class Options
{
	private string $capath = '';

	private string $host = '';

	private string $verb = 'GET';

	private string $endpoint = 'index.php';

	private string $component = '';

	private string $view = '';

	private string $format = '';

	private string $secret = '';

	private string $ua = 'AkeebaRemoteCLI/' . ARCCLI_VERSION;

	private bool $verbose = false;

	private bool $isWordPress = false;

	private ?LoggerInterface $logger;

	/**
	 * OutputOptions constructor. The options you pass initialize the immutable object.
	 *
	 * @param   array  $options  The options to initialize the object with
	 * @param   bool   $strict   When enabled, unknown $options keys will throw an exception instead of silently
	 *                           skipped.
	 */
	public function __construct(array $options, bool $strict = false)
	{
		$this->logger = new NullLogger();

		foreach ($options as $k => $v)
		{
			if (property_exists($this, $k))
			{
				$this->$k = $v;
			}

			if ($strict)
			{
				throw new \LogicException(
					sprintf(
						'Class %s does not have property ‘%s’',
						__CLASS__,
						$k
					)
				);
			}

			continue;
		}

		if ($options['debug'] ?? false)
		{
			$this->verbose = true;
		}

		// Make sure we have a secret
		if (empty($this->secret))
		{
			throw new NoConfiguredSecret();
		}

		// Normalize the host definition
		$this->parseHost();

		if (empty($this->host))
		{
			throw new NoConfiguredHost();
		}

		// Akeeba Solo or Akeeba Backup for WordPress endpoint; do not use format and component parameters in the URL
		if ($this->endpoint == 'remote.php')
		{
			$this->format    = '';
			$this->component = '';
		}
		// Akeeba Solo or Akeeba Backup for WordPress endpoint; do not use format and component parameters in the URL
		elseif ($this->endpoint == 'admin-ajax.php')
		{
			$this->format      = '';
			$this->component   = '';
			$this->isWordPress = true;
		}

		// Make sure I have a valid CA cache path
		if (empty($this->capath) || !CaBundle::validateCaFile($this->capath))
		{
			$this->capath = CaBundle::getSystemCaRootBundlePath();
		}
	}

	/**
	 * Magic getter, used to implement read only properties.
	 *
	 * @param   string  $name  The name of the property being read
	 *
	 * @return  mixed
	 */
	public function __get(string $name)
	{
		if (property_exists($this, $name))
		{
			return $this->$name;
		}

		throw new \LogicException(
			sprintf(
				'Class %s does not have property ‘%s’',
				__CLASS__,
				$name
			)
		);
	}

	public function toArray(): array
	{
		$currentOptions = [];

		foreach ($this as $k => $v)
		{
			$currentOptions[$k] = $v;
		}

		return $currentOptions;
	}

	/**
	 * Gets an exact copy of the object with the new options overriding the current ones
	 *
	 * @param   array  $options  The options you are overriding
	 *
	 * @return  self
	 */
	public function getModifiedClone(array $options = []): self
	{
		$currentOptions = $this->toArray();
		$options        = array_replace_recursive($currentOptions, $options);

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

		// The path was "some/thing/whatever.ext". If .ext is .php I have an endpoint. Otherwise, I will strip it.
		if (substr($endpoint, -4) == '.php')
		{
			return [$path, $endpoint];
		}

		return [$path, ''];
	}
}
