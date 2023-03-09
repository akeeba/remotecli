<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\HighLevel;

use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\Exception\ApiException;
use Akeeba\RemoteCLI\Api\Exception\CommunicationError;
use Akeeba\RemoteCLI\Api\Exception\InvalidSecretWord;
use Akeeba\RemoteCLI\Api\Exception\NoWayToConnect;
use Akeeba\RemoteCLI\Api\Exception\RemoteApiVersionTooLow;
use Akeeba\RemoteCLI\Api\Exception\RemoteError;
use Akeeba\RemoteCLI\Api\Options;

/**
 * Auto-detect the best connection settings
 */
class Autodetect
{
	public function __construct(private Connector $connector)
	{
	}

	public function __invoke(): void
	{
		$originalOptions = $this->connector->getOptions();
		$view            = strtolower($originalOptions->view ?? 'api');
		$verbs           = $this->getVerbs($originalOptions);
		$formats         = $this->getFormats($originalOptions);
		$endpoints       = $this->getEndpoints($originalOptions);
		$components      = $this->getComponents($originalOptions);

		$apiResult     = null;
		$lastException = null;

		foreach ($components as $component)
		{
			foreach ($verbs as $verb)
			{
				foreach ($formats as $format)
				{
					foreach ($endpoints as $endpoint)
					{
						$lastException = null;

						$options = $this->connector->getOptions([
							'component' => $component,
							'verb'      => $verb,
							'view'      => $view,
							'format'    => $format,
							'endpoint'  => $endpoint,
						]);

						try
						{
							$apiResult = null;
							$api       = new Connector($options);
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

							$options->logger->warning(sprintf(
									'Communication error with verb “%s”, view “%s”, format “%s”, endpoint “%s”. The error was ‘%s’.',
									$verb,
									$view,
									$format,
									$endpoint,
									$communicationError->getMessage()
								)
							);

							$lastException = $communicationError;

							continue;
						}
						catch (InvalidSecretWord $apiException)
						{
							// Invalid secret word exception gets re-thrown
							throw $apiException;
						}
						catch (ApiException $apiException)
						{
							$lastException = $apiException;

							/**
							 * We got corrupt data back. This could be because, e.g. using the format=html on a Joomla! site
							 * with a broken third party plugin results in the output being ovewritten. So let's retry with
							 * another way to connect to the site.
							 */
							$options->logger->warning(sprintf(
									'Remote API error with verb “%s”, format “%s”, endpoint “%s”. The error was ‘%s’.',
									$verb,
									$format,
									$endpoint,
									$apiException->getMessage()
								)
							);

							continue;
						}
					}
				}
			}
		}

		if (is_null($apiResult))
		{
			throw new NoWayToConnect(36, $lastException);
		}

		// Check the response
		if ($apiResult->body->status != 200)
		{
			throw new RemoteError($apiResult->body->status . " - " . $apiResult->body->data, 101, $lastException);
		}

		// Check the API version
		if ($apiResult->body->data->api < ARCCLI_MINAPI)
		{
			throw new RemoteApiVersionTooLow(102, $lastException);
		}

		$this->connector->setOptions($options);
	}

	/**
	 * Get the component (option) list I will be testing for.
	 *
	 * @param   Options  $options  The parsed options
	 *
	 * @return  string[]
	 */
	private function getComponents(Options $options): array
	{
		$defaultComponents = ['com_akeebabackup', 'com_akeeba', ''];

		if ($options->component === null)
		{
			return $defaultComponents;
		}

		return [strtolower($options->component ?: null)];
	}

	/**
	 * Get the formats I will be testing for.
	 *
	 * @param   Options  $options  The application input object
	 *
	 * @return  array
	 */
	private function getEndpoints(Options $options): array
	{
		$defaultList = ['index.php', 'remote.php', 'wp-admin/admin-ajax.php'];
		$endpoint    = $options->endpoint;

		return empty($endpoint) ? $defaultList : [$endpoint];
	}

	/**
	 * Get the formats I will be testing for
	 *
	 * @param   Options  $options  The parsed options
	 *
	 * @return  array
	 */
	private function getFormats(Options $options): array
	{
		$defaultFormats = ['json', 'raw'];
		$format         = strtolower($options->format ?: '');
		$format         = in_array($format, $defaultFormats, true) ? $format : '';

		if (empty($format))
		{
			return $defaultFormats;
		}

		return [$format];
	}

	/**
	 * Get the verbs I will be testing for.
	 *
	 * @param   Options  $options  The parsed options
	 *
	 * @return  array
	 */
	private function getVerbs(Options $options): array
	{
		$defaultList = ['POST', 'GET'];
		$verb        = strtoupper($options->verb ?: '');

		if (!in_array($verb, $defaultList))
		{
			return $defaultList;
		}

		return [$verb];
	}
}