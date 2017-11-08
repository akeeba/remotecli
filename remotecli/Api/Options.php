<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Api;


/**
 * Immutable options for the API
 *
 * @property-read   string  $host
 * @property-read   string  $secret
 * @property-read   string  $endpoint
 * @property-read   string  $component
 * @property-read   string  $verb
 * @property-read   string  $format
 * @property-read   string  $ua
 * @property-read   bool    $verbose
 * @property-read   int     $encapsulation
 */
class Options
{
	const ENC_RAW = 1;
	const ENC_CTR128 = 2;
	const ENC_CBC128 = 4;

	private $host;
	private $secret;
	private $endpoint = 'index.php';
	private $component = 'com_akeeba';
	private $verb = 'GET';
	private $format = 'html';
	private $ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';
	private $verbose = false;
	private $encapsulation = self::ENC_CBC128;

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

		// Akeeba Solo or Akeeba Backup for WordPress endpoint; do not use format and component parameters in the URL
		if ($this->endpoint == 'remote.php')
		{
			$this->format = '';
			$this->component = '';
		}

		if (is_string($this->encapsulation))
		{
			switch (strtoupper($this->encapsulation))
			{
				case 'RAW':
					$this->encapsulation = self::ENC_RAW;
					break;

				case 'CTR128':
				case 'CTR256':
					$this->encapsulation = self::ENC_CTR128;
					break;

				case 'CBC128':
				case 'CBC256':
				default:
					$this->encapsulation = self::ENC_CBC128;
					break;
			}
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

		$options = array_merge_recursive($currentOptions, $options);

		return new self($options);
	}
}
