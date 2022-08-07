<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Api;

use Akeeba\RemoteCLI\Download\Download;
use Akeeba\RemoteCLI\Encrypt\RandomValue;
use Akeeba\RemoteCLI\Exception\CommunicationError;
use Akeeba\RemoteCLI\Exception\InvalidEncapsulatedJSON;
use Akeeba\RemoteCLI\Exception\InvalidJSONBody;
use Akeeba\RemoteCLI\Output\Output;
use Akeeba\RemoteCLI\Utility\Uri;

class Api
{
	/**
	 * The API options object.
	 *
	 * @var   Options
	 */
	private $options;

	/**
	 * The output handler object.
	 *
	 * @var   Output
	 */
	private $output;

	/**
	 * The download object which is used to fetch information from the remote server.
	 *
	 * @var   Download
	 */
	private $fetcher;

	/**
	 * The private key used in encrypted API responses.
	 *
	 * @var   string
	 */
	private $responseKey = '';

	/**
	 * Api constructor.
	 *
	 * @param   Options  $options  The API options
	 * @param   Output   $output   The output handler, used in verbose mode
	 */
	public function __construct(Options $options, Output $output)
	{
		$this->options = $options;
		$this->output  = $output;
		$this->fetcher = new Download();

		switch (strtolower($this->fetcher->getAdapterName()))
		{
			case 'curl':
				$this->fetcher->setAdapterOptions([
					CURLOPT_CAINFO => $options->capath,
				]);
				break;

			case 'fopen':
				$this->fetcher->setAdapterOptions([
					'ssl' => [
						'cafile' => $options->capath,
					],
				]);
				break;
		}
	}

	/**
	 * Perform a remote JSON API query and returns the server result object.
	 *
	 * @param   string  $apiMethod  The API method to execute.
	 * @param   array   $data       THe data to send to the API method.
	 *
	 * @return  object  The response object per the JSON API documentation.
	 *
	 * @throws InvalidJSONBody When the body->data contains invalid JSON.
	 * @throws InvalidEncapsulatedJSON When the encapsulated result we read from the server is not valid JSON.
	 * @throws CommunicationError When there is a network or other error trying to communicate with the server.
	 */
	public function doQuery(string $apiMethod, array $data = []): object
	{
		$this->output->debug('Data: ' . print_r($data, true));

		// Generate a random key the server will use to encrypt its response back to us
		if ($this->options->view == 'json')
		{
			$this->genRandomKey();
			$this->output->debug('Response Key: ' . $this->responseKey);
		}

		$url = $this->getURL($apiMethod, $data);

		$this->output->debug('URL: ' . $url);

		if ($this->options->verb == 'POST')
		{
			$payload = http_build_query($this->getQueryStringParameters($apiMethod, $data));
			$raw     = $this->fetcher->postToURL($url, $payload);
		}
		else
		{
			$raw = $this->fetcher->getFromURL($url);
		}

		// Extract the encapsulated response (placed between ### markers) from whatever the server sent back to us.
		$encapsulatedResponse = $this->extractEncapsulatedResponse($raw);

		if ($this->options->verbose)
		{
			$this->output->debug('Raw Response: ' . $encapsulatedResponse);
		}

		// Expose the encapsulated data
		if ($this->options->view == 'json')
		{
			// Legacy v1 API: unwrap the data
			$result = $this->exposeData($encapsulatedResponse);

			if ($this->options->verbose)
			{
				$this->output->debug('Parsed Response: ' . print_r($result, true));
			}

			// Decode the JSON encoded body
			$result->body->data = json_decode($result->body->data, false);

			if (is_null($result->body->data))
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
			$this->output->debug('Parsed Response: ' . print_r($result, true));
		}

		return (object) [
			'body' => $result,
		];
	}

	/**
	 * Gets a copy of the Api options being used. You can supply $overrides to override some or all of these options.
	 *
	 * @param   array  $overrides  Option overrides. Leave empty to get a copy of the options currently in use.
	 *
	 * @return  Options
	 */
	public function getOptions(array $overrides = []): Options
	{
		return $this->options->getModifiedClone($overrides);
	}

	/**
	 * Get the URL for an API call.
	 *
	 * @param   string  $apiMethod  The Akeeba Backup / Solo JSON API method to execute.
	 * @param   array   $data       The data being sent to the JSON API in array format.
	 * @param   bool    $forceGET   Force use of GET? Default false. Use to make signed download URLs.
	 *
	 * @return  string  The URL we are supposed to access
	 */
	public function getURL(string $apiMethod, array $data = [], bool $forceGET = false): string
	{
		// Extract options. DO NOT REMOVE. empty() does NOT work on magic properties!
		$url         = rtrim($this->options->host, '/');
		$endpoint    = $this->options->endpoint;
		$verb        = $this->options->verb;
		$isWordPress = $this->options->isWordPress ?? false;

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

		return $uri->toString();
	}

	/**
	 * Get the query string parameters (data payload) for an API request.
	 *
	 * @param   string  $apiMethod  The Akeeba Backup / Solo JSON API method to execute.
	 * @param   array   $data       The data being sent to the JSON API in array format.
	 *
	 * @return  array
	 */
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
			if ((($format == 'html') || empty($format)) && !empty($component))
			{
				$params['tmpl'] = 'component';
			}
		}

		return $params;
	}

	/**
	 * Encapsulate the data used for an API call into a (possibly encrypted) JSON string.
	 *
	 * @param   string  $apiMethod  The API method you are calling
	 * @param   array   $data       The data sent to the API method
	 *
	 * @return  string  The encoded JSON string
	 */
	private function encapsulateData(string $apiMethod, array $data): string
	{
		$body = [
			'method' => $apiMethod,
			'data'   => $data,
		];

		$randVal           = new RandomValue();
		$salt              = $randVal->generateString(32);
		$challenge         = $salt . ':' . md5($salt . $this->options->secret);
		$body['challenge'] = $challenge;

		$bodyData = json_encode($body);

		$jsonSource = [
			'encapsulation' => 1,
			'body'          => $bodyData,
		];

		return json_encode($jsonSource);
	}

	/**
	 * Take the encapsulated data and take the necessary steps to convert them to plain data you can use in the
	 * application.
	 *
	 * @param   string  $encapsulated
	 *
	 * @return  object  The ->body->data property contains the returned data from the server
	 */
	private function exposeData(string $encapsulated): object
	{
		$result = json_decode($encapsulated, false);

		if (is_null($result) || !property_exists($result, 'body') || !property_exists($result->body, 'data'))
		{
			throw new InvalidEncapsulatedJSON($encapsulated);
		}

		return $result;
	}

	/**
	 * Create a random encryption key and store it in $this->responseKey
	 *
	 * @return  void
	 */
	private function genRandomKey(): void
	{
		$randval           = new RandomValue();
		$this->responseKey = $randval->generateString(64);
	}

	/**
	 * @param   string  $raw
	 *
	 * @return  string
	 */
	private function extractEncapsulatedResponse(string $raw): string
	{
		$startPos = strpos($raw, '###') + 3;
		$endPos   = strrpos($raw, '###');
		$json     = $raw;

		if (($startPos !== false) && ($endPos !== false))
		{
			$json = substr($raw, $startPos, $endPos - $startPos);
		}

		return $json;
	}
}
