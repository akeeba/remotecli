<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Model;


use Akeeba\RemoteCLI\Api\Api;
use Akeeba\RemoteCLI\Api\Options;
use Akeeba\RemoteCLI\Exception\ApiException;
use Akeeba\RemoteCLI\Exception\CommunicationError;
use Akeeba\RemoteCLI\Exception\NoWayToConnect;
use Akeeba\RemoteCLI\Exception\RemoteApiVersionTooLow;
use Akeeba\RemoteCLI\Exception\RemoteError;
use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Output\Output;

class Test
{
	/**
	 * Explores the best API connection method (verb, format, endpoint) OR uses the ones defined in the command line to
	 * connect to the remote site. Then it checks that the remote site is on the correct API level for this script. It
	 * returns the Options for the best detected connection method.
	 *
	 * @param   Cli      $input            The input object.
	 * @param   Output   $output           The output object.
	 * @param   Options  $originalOptions  The API options. The format, verb and endpoint options _may_ be overwritten.
	 *
	 * @return  Options
	 */
	public function getBestOptions(Cli $input, Output $output, Options $originalOptions)
	{
		[$options,] = $this->getBestApiAndInformation($input, $output, $originalOptions);

		return $options;
	}

	/**
	 * Explores the best API connection method (verb, format, endpoint) OR uses the ones defined in the command line to
	 * connect to the remote site. Then it checks that the remote site is on the correct API level for this script. It
	 * returns only the API return object.
	 *
	 * @param   Cli      $input            The input object.
	 * @param   Output   $output           The output object.
	 * @param   Options  $originalOptions  The API options. The format, verb and endpoint options _may_ be overwritten.
	 *
	 * @return  Options
	 */
	public function getApiInformation(Cli $input, Output $output, Options $originalOptions)
	{
		[, $apiresult] = $this->getBestApiAndInformation($input, $output, $originalOptions);

		return $apiresult;
	}

	/**
	 * Find the best API connection method to the site.
	 *
	 * First we try with the v2 API (view=Api) using format=json|raw, HTTP POST|GET and endpoint index.php|remote.php.
	 *
	 * Then we try the v1 API (view=json) using format=raw|html|(none), HTTP POST|GET and endpoint index.php|remote.php.
	 *
	 * Assuming we manage to find a way to connect we then verify that the remote site is on the correct API level for
	 * this script.
	 *
	 * This method returns both the API options and the API test information.
	 *
	 * @param   Cli      $input            The input object.
	 * @param   Output   $output           The output object.
	 * @param   Options  $originalOptions  The API options. The format, verb and endpoint options _may_ be overwritten.
	 *
	 * @return  array  [Api, Options]
	 */
	private function getBestApiAndInformation(Cli $input, Output $output, Options $originalOptions)
	{
		$apiResult = null;
		$api       = null;

		//foreach (['api', 'json'] as $view)
		foreach (['api'] as $view)
		{
			$verbs   = $this->getVerbs($input);
			$format  = $this->getFormat($input);
			$formats = [$format];

			if (empty($format))
			{
				$formats = ($view == 'json') ? ['raw', 'html', ''] : ['json', 'raw'];
			}

			$endpoints      = $this->getEndpoints($originalOptions);
			$encapsulation = ($view == 'json') ? Options::ENC_RAW : Options::ENC_NONE;

			foreach ($verbs as $verb)
			{
				foreach ($formats as $format)
				{
					foreach ($endpoints as $endpoint)
					{
						$options = $originalOptions->getModifiedClone([
							'verb'          => $verb,
							'view'          => $view,
							'format'        => $format,
							'endpoint'      => $endpoint,
							'encapsulation' => $encapsulation,
						]);

						try
						{
							$apiResult = null;
							$api       = new Api($options, $output);
							$apiResult = $api->doQuery('getVersion');

							// This happens if we use the wrong encapsulation
							if ($apiResult->body->status != 200)
							{
								$apiResult = null;

								continue;
							}

							break 4;
						}
						catch (CommunicationError $communicationError)
						{
							/**
							 * We might get this kind of exception if the endpoint is wrong or results in endless
							 * redirections. Of course it's also raised when it's a genuine network issue but, hey, what can
							 * you do?
							 */

							if ($options->verbose)
							{
								$output->warning(sprintf(
										'Communication error with verb “%s”, view “%s”, format “%s”, endpoint “%s”, encapsulation “%s”. The error was ‘%s’.',
										$verb,
										$view,
										$format,
										$endpoint,
										$encapsulation,
										$communicationError->getMessage()
									)
								);
							}

							continue;
						}
						catch (ApiException $apiException)
						{
							/**
							 * We got corrupt data back. This could be because, e.g. using the format=html on a Joomla! site
							 * with a broken third party plugin results in the output being ovewritten. So let's retry with
							 * another way to connect to the site.
							 */
							if ($options->verbose)
							{
								$output->warning(sprintf(
										'Remote API error with verb “%s”, format “%s”, endpoint “%s”, encapsulation “%s”. The error was ‘%s’.',
										$verb,
										$format,
										$endpoint,
										$encapsulation,
										$apiException->getMessage()
									)
								);
							}

							continue;
						}
					}
				}
			}

		}

		if (is_null($apiResult))
		{
			throw new NoWayToConnect();
		}

		// Check the response
		if ($apiResult->body->status != 200)
		{
			throw new RemoteError($apiResult->body->status . " - " . $apiResult->body->data);
		}

		$versionInfo = $apiResult->body->data;

		// Check the API version
		if ($apiResult->body->data->api < ARCCLI_MINAPI)
		{
			throw new RemoteApiVersionTooLow();
		}

		return [$api->getOptions(), $apiResult];
	}

	/**
	 * Get the verbs I will be testing for.
	 *
	 * @param   Cli  $input  The application input object
	 *
	 * @return  array
	 */
	private function getVerbs(Cli $input)
	{
		$defaultList = ['POST', 'GET'];
		$verb        = $input->getCmd('verb', '');
		$verb        = strtoupper($verb);

		if (!in_array($verb, $defaultList))
		{
			$verb = '';
		}

		return empty($verb) ? $defaultList : [$verb];
	}

	/**
	 * Get the formats I will be testing for.
	 *
	 * @param   Cli  $input  The application input object
	 *
	 * @return  string
	 */
	private function getFormat(Cli $input)
	{
		$defaultList = ['json', 'html', 'raw', ''];
		$format      = $input->getCmd('format', null);
		$format      = strtolower($format);

		if (!in_array($format, $defaultList, true))
		{
			$format = '';
		}

		return $format;
	}

	/**
	 * Get a list of the encapsulations to try using
	 *
	 * @return  array
	 */
	private function getEncapsulations()
	{
		return [
			// Options::ENC_NONE,
			Options::ENC_RAW,
		];
	}

	/**
	 * Get the formats I will be testing for.
	 *
	 * @param   Options  $options  The application input object
	 *
	 * @return  array
	 */
	private function getEndpoints(Options $options)
	{
		$defaultList = ['index.php', 'remote.php'];
		$endpoint    = $options->endpoint;

		return empty($endpoint) ? $defaultList : [$endpoint];
	}
}
