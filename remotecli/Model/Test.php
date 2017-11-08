<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
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
	 * connect to the remote site. Then it checks that the remote site is on the correct API level for this script.
	 *
	 * @param   Cli      $input            The input object.
	 * @param   Output   $output           The output object.
	 * @param   Options  $originalOptions  The API options. The format, verb and endpoint options _may_ be overwritten.
	 *
	 * @return  Api
	 */
	public function getBestApi(Cli $input, Output $output, Options $originalOptions)
	{
		$verbs     = $this->getVerbs($input);
		$formats   = $this->getFormats($input);
		$endpoints = $this->getEndpoints($originalOptions);
		$apiResult = null;
		$api       = null;

		foreach ($verbs as $verb)
		{
			foreach ($formats as $format)
			{
				foreach ($endpoints as $endpoint)
				{
					$options = $originalOptions->getModifiedClone([
						'verb'     => $verb,
						'format'   => $format,
						'endpoint' => $endpoint,
					]);

					try
					{
						$apiResult = null;
						$api       = new Api($options, $output);
						$apiResult = $api->doQuery('getVersion');

						break 3;
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
							$output->debug(sprintf(
								'Communication error with verb “%s”, format “%s”, endpoint “%s”. The error was ‘%s’.',
								$verb,
								$format,
								$endpoint,
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
							$output->debug(sprintf(
									'Remote API error with verb “%s”, format “%s”, endpoint “%s”. The error was ‘%s’.',
									$verb,
									$format,
									$endpoint,
									$apiException->getMessage()
								)
							);
						}

						continue;
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

		$version = $versionInfo->component . ' (API level ' . $apiResult->body->data->api . ')';
		$edition = ($versionInfo->edition == 'pro') ? 'Professional' : 'Core';

		$output->info("Successful connection to site");
		$output->info("Akeeba Backup / Solo $edition $version");
		$output->info('');

		return $api;
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
		$defaultList = ['GET', 'POST'];
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
	 * @return  array
	 */
	private function getFormats(Cli $input)
	{
		$defaultList = ['html', 'raw', ''];
		$format      = $input->getCmd('format', null);
		$format      = strtolower($format);

		if (!in_array($format, $defaultList, true))
		{
			$format = '';
		}

		return empty($format) ? $defaultList : [$format];
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
