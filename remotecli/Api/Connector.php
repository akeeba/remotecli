<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api;

use Akeeba\RemoteCLI\Api\DataShape\BackupOptions;
use Akeeba\RemoteCLI\Api\DataShape\DownloadOptions;
use Akeeba\RemoteCLI\Api\Exception\InvalidEncapsulatedJSON;
use Akeeba\RemoteCLI\Api\Exception\InvalidJSONBody;
use Akeeba\RemoteCLI\Api\Exception\InvalidSecretWord;
use Akeeba\RemoteCLI\Api\Exception\UnknownMethod;
use Joomla\Http\Http;
use Joomla\Http\HttpFactory;
use Joomla\Uri\Uri;
use Psr\Log\LoggerInterface;

/**
 * Akeeba Backup JSON API connector
 *
 * @method  void    autodetect()  Auto-detect best connection options
 * @method  object  information()  Get API information
 * @method  object  backup(BackupOptions $backupOptions, ?callable $progressCallback = null) Start a backup
 * @method  array   getBackups(int $from = 0, $limit = 200)  List backups
 * @method  object  getBackup(int $id = 0)  Get a backup record
 * @method  void    deleteFiles(int $id) Delete the files of a backup record
 * @method  void    delete(int $id) Delete a backup record
 * @method  void    download(DownloadOptions $options) Download a backup record
 * @method  array   getProfiles() Get the backup profiles
 * @method  array   importConfiguration(string $jsonData) Import a backup profile from JSON
 * @method  array   exportConfiguration(int $id) Export a backup profile to JSON
 * @method  object  getUpdateInformation(bool $force = false) Get the update information of the backup product
 * @method  void    downloadUpdate() Download the update package to the server
 * @method  void    extractUpdate() Extracts the update package to the server
 * @method  void    installUpdate() Performs the necessary installation steps for the update on the server
 * @method  void    cleanupUpdate() Cleans up the download update package on the server
 *
 * @since 3.0.0
 */
class Connector
{
	private Http $http;

	private LoggerInterface $logger;

	private array $callables = [];

	public function __construct(private Options $options)
	{
		$this->applyOptions();
	}

	public function __call(string $name, array $arguments)
	{
		if (!isset($this->callables[$name]))
		{
			$class = __NAMESPACE__ . '\\HighLevel\\' . ucfirst($name);

			if (!(class_exists($class, true)))
			{
				throw new \BadMethodCallException(
					sprintf(
						'Unknown method %s->%s()',
						__CLASS__,
						$name
					),
					255
				);
			}

			$this->callables[$name] = new $class($this);
		}

		return call_user_func($this->callables[$name], ...$arguments);
	}

	public function doQuery(string $apiMethod, array $data = []): object
	{
		$url = $this->makeURL($apiMethod, $data);

		$this->logger->debug(sprintf('Sending Akeeba Backup / Akeeba Solo JSON API request for method %s with %s', $apiMethod, $this->options->verb));
		$this->logger->debug('URL: ' . $url);
		$this->logger->debug('>> Data:' . PHP_EOL . print_r($data, true));

		if ($this->options->verb == 'POST')
		{
			$payload = http_build_query($this->getQueryStringParameters($apiMethod, $data));
			$raw     = $this->http->post($url, $payload)->body;
		}
		else
		{
			$raw = $this->http->get($url)->body;
		}

		// Extract the encapsulated response (placed between ### markers) from whatever the server sent back to us.
		$encapsulatedResponse = $this->extractEncapsulatedResponse($raw);

		if ($this->options->verbose)
		{
			$this->logger->debug('<< Response: ' . PHP_EOL . $encapsulatedResponse);
		}

		// Expose the encapsulated data
		if ($this->options->view == 'json')
		{
			// Legacy v1 API: unwrap the data
			$result = $this->exposeData($encapsulatedResponse);

			if ($this->options->verbose)
			{
				$this->logger->debug('Parsed Response: ' . PHP_EOL . print_r($result, true));
			}

			// Decode the JSON encoded body
			try
			{
				$result->body->data = @json_decode($result->body->data, false);
			}
			catch (\Exception $e)
			{
				$result->body->data = null;
			}

			if ($result->body->data === null)
			{
				throw new InvalidJSONBody();
			}

			return $result;
		}

		// JSON API v2: Get the JSON data and construct a result similar to what was returned by v1
		$result = json_decode($encapsulatedResponse, false);

		if (is_null($result) || !property_exists($result, 'status') || !property_exists($result, 'data'))
		{
			throw new InvalidEncapsulatedJSON($encapsulatedResponse);
		}

		if ($this->options->verbose)
		{
			$this->logger->debug('Parsed Response: ' . print_r($result, true));
		}

		$apiResult = (object) [
			'body' => $result,
		];

		if ($apiResult->body->status !== 200)
		{
			$this->logger->notice(
				sprintf('Error status %d received from the API.', $apiResult->body->status)
			);
		}

		if ($apiResult->body->status === 405)
		{
			throw new UnknownMethod(
				sprintf('Server responded it does not know of API method %s. Is your installation broken?', $apiMethod)
			);
		}

		if ($apiResult->body->status === 503)
		{
			throw new InvalidSecretWord();
		}

		return $apiResult;
	}

	public function makeURL(string $apiMethod, array $data = [], bool $forceGET = false): string
	{
		// Extract options. DO NOT REMOVE. empty() does NOT work on magic properties!
		$url         = rtrim($this->options->host, '/');
		$endpoint    = $this->options->endpoint;
		$verb        = $this->options->verb;
		$isWordPress = $this->options->isWordPress;

		if (!empty($endpoint))
		{
			$url .= '/' . $endpoint;
		}

		// For v2 URLs we need to add the authentication as a GET parameter
		$uri = new Uri($url);

		if ($this->options->view == 'api')
		{
			$uri->setVar('_akeebaAuth', $this->options->secret);
		}

		if ($isWordPress)
		{
			$uri->setVar('action', 'akeebabackup_api');
		}

		// If we're doing POST requests there's nothing more to do
		if (!$forceGET && ($verb == 'POST'))
		{
			return $uri->toString();
		}

		// For GET requests we have to add the entire payload as query string parameters
		foreach ($this->getQueryStringParameters($apiMethod, $data) as $k => $v)
		{
			$uri->setVar($k, $v);
		}

		if ($isWordPress)
		{
			$uri->delVar('option');
			$uri->delVar('view');
			$uri->delVar('format');
		}

		// Work around the Joomla Framework mysteriously choosing to URL-decode the query string, breaking the URL...
		$query = $uri->getQuery(true);
		$uri->setQuery('');

		return $uri->toString() . '?' . http_build_query($query, '', null, PHP_QUERY_RFC3986);
	}

	public function getOptions(array $overrides = []): Options
	{
		return $this->options->getModifiedClone($overrides);
	}

	public function setOptions(Options $options): void
	{
		$this->options = $options;

		$this->applyOptions();
	}

	private function applyOptions()
	{
		$this->logger = $this->options->logger;

		$this->http = (new HttpFactory())->getHttp(
			[
				'curl.certpath'   => $this->options->capath,
				'follow_location' => 1,
			],
			['curl']
		);
	}

	private function getQueryStringParameters(string $apiMethod, array $data = []): array
	{
		switch ($this->options->view ?? 'json')
		{
			case 'json':
			default:
				$params = [
					'view' => 'json',
					'json' => $this->encapsulateData($apiMethod, $data),
				];
				break;

			case 'api':
				$params = array_merge($data, ['view' => 'Api', 'method' => $apiMethod]);
				break;
		}

		// DO NOT REMOVE. empty() does NOT work on magic properties!
		$component = $this->options->component;
		$format    = $this->options->format;

		if (!empty($component))
		{
			$params['option'] = $component;
		}

		if (!empty($format))
		{
			$params['format'] = $format;

			/**
			 * If it's Joomla! we have to set tmpl=component to avoid template interference if the format is set to
			 * 'html' on an empty string (which is equivalent to 'html' as it's the default).
			 */
			if (($format == 'html') && !empty($component))
			{
				$params['tmpl'] = 'component';
			}
		}

		return $params;
	}

	private function encapsulateData(string $apiMethod, array $data): string
	{
		$body = [
			'method' => $apiMethod,
			'data'   => $data,
		];

		$salt              = $this->randomString();
		$challenge         = $salt . ':' . md5($salt . $this->options->secret);
		$body['challenge'] = $challenge;

		$bodyData = json_encode($body);

		$jsonSource = [
			'encapsulation' => 1,
			'body'          => $bodyData,
		];

		return json_encode($jsonSource);
	}

	private function exposeData(string $encapsulated): object
	{
		$result = json_decode($encapsulated, false);

		if (is_null($result) || !property_exists($result, 'body') || !property_exists($result->body, 'data'))
		{
			throw new InvalidEncapsulatedJSON($encapsulated);
		}

		return $result;
	}

	private function extractEncapsulatedResponse(string $raw): string
	{
		$startPos = strpos($raw, '###');
		$endPos   = strrpos($raw, '###');
		$json     = $raw;

		if (($startPos !== false) && ($endPos !== false))
		{
			return substr($raw, $startPos + 3, $endPos - $startPos - 3);
		}

		return $this->extractResponseAmongstPHPErrorOutput($json) ?? '';
	}

	private function extractResponseAmongstPHPErrorOutput(string $raw): ?string
	{
		try
		{
			$test = @json_decode($raw);

			if ($test !== null)
			{
				return $raw;
			}
		}
		catch (\Exception $e)
		{
			// No worries
		}

		// Remove obvious garbage
		$openBrace = strpos($raw,'{');
		$closeBrace = strrpos($raw, '}');

		if ($openBrace === false || $closeBrace === false)
		{
			return null;
		}

		$raw = substr($raw, $openBrace, $closeBrace);
		$tries = 0;

		do {
			$tries++;

			if (empty($raw) || $tries > 1000)
			{
				break;
			}

			try
			{
				$test = @json_decode($raw);
			}
			catch (\Exception $e)
			{
				// No worries
			}

			if ($test !== null)
			{
				return $raw;
			}

			$openBrace = strpos($raw,'{', 1);

			if ($openBrace === false)
			{
				break;
			}

			$raw = substr($raw, $openBrace);
		} while (true);

		return null;
	}

	private function randomString(): string
	{
		$sourceString = str_split('abcdefghijklmnopqrstuvwxyz-ABCDEFGHIJKLMNOPQRSTUVWXYZ_0123456789');
		$ret          = '';

		$bytes     = ceil(32 / 4) * 3;
		$randBytes = random_bytes($bytes);

		for ($i = 0; $i < $bytes; $i += 3)
		{
			$subBytes = substr($randBytes, $i, 3);
			$subBytes = str_split($subBytes);
			$subBytes = ord($subBytes[0]) * 65536 + ord($subBytes[1]) * 256 + ord($subBytes[2]);
			$subBytes = $subBytes & bindec('00000000111111111111111111111111');

			$b    = [];
			$b[0] = $subBytes >> 18;
			$b[1] = ($subBytes >> 12) & bindec('111111');
			$b[2] = ($subBytes >> 6) & bindec('111111');
			$b[3] = $subBytes & bindec('111111');

			$ret .= $sourceString[$b[0]] . $sourceString[$b[1]] . $sourceString[$b[2]] . $sourceString[$b[3]];
		}

		return substr($ret, 0, 32);
	}

}
