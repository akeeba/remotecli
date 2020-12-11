<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Utility;

/**
 * URI parsing helper
 */
class Uri
{
	/** @var   string  Original URI */
	protected $uri = null;

	/** @var   string  Protocol */
	protected $scheme = null;

	/** @var   string  Host */
	protected $host = null;

	/** @var   integer  Port */
	protected $port = null;

	/** @var   string  Username */
	protected $user = null;

	/** @var   string  Password */
	protected $pass = null;

	/** @var   string  Path */
	protected $path = null;

	/** @var   string  Query */
	protected $query = null;

	/** @var   string  Anchor */
	protected $fragment = null;

	/** @var   array  Query variable hash */
	protected $vars = [];

	/** @var   array  An array of \Awf\Uri\Uri instances. */
	protected static $instances = [];

	/** @var   array  The current calculated base url segments. */
	protected static $base = [];

	/** @var   array  The current calculated root url segments. */
	protected static $root = [];

	/** @var   string  The current URI */
	protected static $current;

	/**
	 * Constructor. Pass a URI string to the constructor to initialise a specific URI.
	 *
	 * @param   string|null  $uri  The optional URI string
	 */
	public function __construct(?string $uri = null)
	{
		if (!is_null($uri))
		{
			$this->parse($uri);
		}
	}

	/**
	 * Magic method to get the string representation of the URI object.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return $this->toString();
	}

	/**
	 * Parse a given URI and populate the class fields.
	 *
	 * @param   string  $uri  The URI string to parse.
	 *
	 * @return  boolean  True on success.
	 */
	public function parse(string $uri): bool
	{
		// Set the original URI to fall back on
		$this->uri = $uri;

		// Parse the URI and populate the object fields. If URI is parsed properly,
		// set method return value to true.

		$parts = self::parse_url($uri);

		$retval = ($parts) ? true : false;

		// We need to replace &amp; with & for parse_str to work right...
		if (isset($parts['query']) && strpos($parts['query'], '&amp;'))
		{
			$parts['query'] = str_replace('&amp;', '&', $parts['query']);
		}

		$this->scheme   = $parts['scheme'] ?? null;
		$this->user     = $parts['user'] ?? null;
		$this->pass     = $parts['pass'] ?? null;
		$this->host     = $parts['host'] ?? null;
		$this->port     = $parts['port'] ?? null;
		$this->path     = $parts['path'] ?? null;
		$this->query    = $parts['query'] ?? null;
		$this->fragment = $parts['fragment'] ?? null;

		// Parse the query

		if (isset($parts['query']))
		{
			parse_str($parts['query'], $this->vars);
		}

		return $retval;
	}

	/**
	 * Returns full uri string.
	 *
	 * @param   array  $parts  An array specifying the parts to render.
	 *
	 * @return  string  The rendered URI string.
	 */
	public function toString(array $parts = ['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment']): string
	{
		// Make sure the query is created
		$query = $this->getQuery();

		$uri = '';
		$uri .= in_array('scheme', $parts) ? (!empty($this->scheme) ? $this->scheme . '://' : '') : '';
		$uri .= in_array('user', $parts) ? $this->user : '';
		$uri .= in_array('pass', $parts) ? (!empty($this->pass) ? ':' : '') . $this->pass . (!empty($this->user) ? '@' : '') : '';
		$uri .= in_array('host', $parts) ? $this->host : '';
		$uri .= in_array('port', $parts) ? (!empty($this->port) ? ':' : '') . $this->port : '';
		$uri .= in_array('path', $parts) ? $this->path : '';
		$uri .= in_array('query', $parts) ? (!empty($query) ? '?' . $query : '') : '';
		$uri .= in_array('fragment', $parts) ? (!empty($this->fragment) ? '#' . $this->fragment : '') : '';

		return $uri;
	}

	/**
	 * Adds a query variable and value, replacing the value if it
	 * already exists and returning the old value.
	 *
	 * @param   string  $name   Name of the query variable to set.
	 * @param   string  $value  Value of the query variable.
	 *
	 * @return  string  Previous value for the query variable.
	 */
	public function setVar(string $name, string $value): ?string
	{
		$tmp = $this->vars[$name] ?? null;

		if (is_null($value))
		{
			if (isset($this->vars[$name]))
			{
				unset($this->vars[$name]);
			}

			return $tmp;
		}

		$this->vars[$name] = $value;

		// Empty the query
		$this->query = null;

		return $tmp;
	}

	/**
	 * Checks if variable exists.
	 *
	 * @param   string  $name  Name of the query variable to check.
	 *
	 * @return  boolean  True if the variable exists.
	 */
	public function hasVar(string $name): bool
	{
		return array_key_exists($name, $this->vars);
	}

	/**
	 * Returns a query variable by name.
	 *
	 * @param   string  $name     Name of the query variable to get.
	 * @param   null    $default  Default value to return if the variable is not set.
	 *
	 * @return  string   Query variables.
	 */
	public function getVar(string $name, $default = null): ?string
	{
		if (array_key_exists($name, $this->vars))
		{
			return $this->vars[$name];
		}

		return $default;
	}

	/**
	 * Removes an item from the query string variables if it exists.
	 *
	 * @param   string  $name  Name of variable to remove.
	 *
	 * @return  void
	 */
	public function delVar(string $name): void
	{
		if (array_key_exists($name, $this->vars))
		{
			unset($this->vars[$name]);

			// Empty the query
			$this->query = null;
		}
	}

	/**
	 * Sets the query to a supplied string in format:
	 * foo=bar&x=y
	 *
	 * @param   string|array  $query  The query string or array.
	 *
	 * @return  void
	 */
	public function setQuery($query): void
	{
		if (is_array($query))
		{
			$this->vars = $query;
		}
		else
		{
			if (strpos($query, '&amp;') !== false)
			{
				$query = str_replace('&amp;', '&', $query);
			}

			parse_str($query, $this->vars);
		}

		// Empty the query
		$this->query = null;
	}

	/**
	 * Returns flat query string.
	 *
	 * @param   boolean  $toArray  True to return the query as a key => value pair array.
	 *
	 * @return  string|array   Query string.
	 */
	public function getQuery(bool $toArray = false)
	{
		if ($toArray)
		{
			return $this->vars;
		}

		// If the query is empty build it first
		if (is_null($this->query))
		{
			$this->query = self::buildQuery($this->vars);
		}

		return $this->query;
	}

	/**
	 * Build a query from a array (reverse of the PHP parse_str()).
	 *
	 * @param   array  $params  The array of key => value pairs to return as a query string.
	 *
	 * @return  string  The resulting query string.
	 *
	 * @see     parse_str()
	 */
	public static function buildQuery(array $params): string
	{
		if (count($params) == 0)
		{
			return false;
		}

		return http_build_query($params, '', '&');
	}

	/**
	 * Get URI scheme (protocol)
	 * ie. http, https, ftp, etc...
	 *
	 * @return  string  The URI scheme.
	 */
	public function getScheme(): ?string
	{
		return $this->scheme;
	}

	/**
	 * Set URI scheme (protocol)
	 * ie. http, https, ftp, etc...
	 *
	 * @param   string  $scheme  The URI scheme.
	 *
	 * @return  void
	 */
	public function setScheme(string $scheme): void
	{
		$this->scheme = $scheme;
	}

	/**
	 * Get URI username
	 * Returns the username, or null if no username was specified.
	 *
	 * @return  string  The URI username.
	 */
	public function getUser(): ?string
	{
		return $this->user;
	}

	/**
	 * Set URI username.
	 *
	 * @param   string|null  $user  The URI username.
	 *
	 * @return  void
	 */
	public function setUser(?string $user): void
	{
		$this->user = $user;
	}

	/**
	 * Get URI password
	 * Returns the password, or null if no password was specified.
	 *
	 * @return  string  The URI password.
	 */
	public function getPass(): ?string
	{
		return $this->pass;
	}

	/**
	 * Set URI password.
	 *
	 * @param   string|null  $pass  The URI password.
	 *
	 * @return  void
	 */
	public function setPass(?string $pass)
	{
		$this->pass = $pass;
	}

	/**
	 * Get URI host
	 * Returns the hostname/ip or null if no hostname/ip was specified.
	 *
	 * @return  string  The URI host.
	 */
	public function getHost(): ?string
	{
		return $this->host;
	}

	/**
	 * Set URI host.
	 *
	 * @param   string|null  $host  The URI host.
	 *
	 * @return  void
	 */
	public function setHost(?string $host)
	{
		$this->host = $host;
	}

	/**
	 * Get URI port
	 * Returns the port number, or null if no port was specified.
	 *
	 * @return  int  The URI port number.
	 */
	public function getPort(): ?int
	{
		return $this->port ?? null;
	}

	/**
	 * Set URI port.
	 *
	 * @param   int|null  $port  The URI port number.
	 *
	 * @return  void
	 */
	public function setPort(?int $port): void
	{
		$this->port = $port;
	}

	/**
	 * Gets the URI path string.
	 *
	 * @return  string  The URI path string.
	 */
	public function getPath(): ?string
	{
		return $this->path;
	}

	/**
	 * Set the URI path string.
	 *
	 * @param   string|null  $path  The URI path string.
	 *
	 * @return  void
	 */
	public function setPath(?string $path): void
	{
		$this->path = $this->_cleanPath($path);
	}

	/**
	 * Get the URI archor string
	 * Everything after the "#".
	 *
	 * @return  string  The URI anchor string.
	 */
	public function getFragment(): ?string
	{
		return $this->fragment;
	}

	/**
	 * Set the URI anchor string
	 * everything after the "#".
	 *
	 * @param   string|null  $anchor  The URI anchor string.
	 *
	 * @return  void
	 */
	public function setFragment(?string $anchor)
	{
		$this->fragment = $anchor;
	}

	/**
	 * Checks whether the current URI is using HTTPS.
	 *
	 * @return  boolean  True if using SSL via HTTPS.
	 */
	public function isSSL(): bool
	{
		return $this->getScheme() == 'https' ? true : false;
	}

	/**
	 * Resolves //, ../ and ./ from a path and returns
	 * the result. Eg:
	 *
	 * /foo/bar/../boo.php    => /foo/boo.php
	 * /foo/bar/../../boo.php => /boo.php
	 * /foo/bar/.././/boo.php => /foo/boo.php
	 *
	 * @param   string  $path  The URI path to clean.
	 *
	 * @return  string  Cleaned and resolved URI path.
	 */
	protected function _cleanPath(string $path): string
	{
		$path = explode('/', preg_replace('#(/+)#', '/', $path));

		for ($i = 0, $n = count($path); $i < $n; $i++)
		{
			if ($path[$i] == '.' || $path[$i] == '..')
			{
				if (($path[$i] == '.') || ($path[$i] == '..' && $i == 1 && $path[0] == ''))
				{
					unset($path[$i]);
					$path = array_values($path);
					$i--;
					$n--;
				}
				elseif ($path[$i] == '..' && ($i > 1 || ($i == 1 && $path[0] != '')))
				{
					unset($path[$i]);
					unset($path[$i - 1]);
					$path = array_values($path);
					$i    -= 2;
					$n    -= 2;
				}
			}
		}

		return implode('/', $path);
	}

	/**
	 * Sanitises and parses a URL using urldecode
	 *
	 * @param   string  $url  The URL to parse
	 *
	 * @return  array  The URL parts from urldecode
	 */
	public static function parse_url(string $url): array
	{
		$result = [];
		// Build arrays of values we need to decode before parsing
		$entities     = [
			'%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%24', '%2C', '%2F', '%3F', '%25',
			'%23', '%5B',
			'%5D',
		];
		$replacements = ['!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "$", ",", "/", "?", "%", "#", "[", "]"];
		// Create encoded URL with special URL characters decoded so it can be parsed
		// All other characters will be encoded
		$encodedURL = str_replace($entities, $replacements, urlencode($url));
		// Parse the encoded URL
		$encodedParts = parse_url($encodedURL);
		// Now, decode each value of the resulting array
		foreach ($encodedParts as $key => $value)
		{
			$result[$key] = urldecode($value);
		}

		return $result;
	}
}
