<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

use Akeeba\BackupJsonApi\Connector;
use Akeeba\BackupJsonApi\DataShape\DownloadOptions;
use Akeeba\BackupJsonApi\Exception\NoConfiguredHost;
use Akeeba\BackupJsonApi\Exception\NoConfiguredSecret;
use Akeeba\BackupJsonApi\HttpAbstraction\HttpClientInterface;
use Akeeba\BackupJsonApi\HttpAbstraction\HttpClientJoomla;
use Akeeba\BackupJsonApi\Options;
use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Kernel\CommandInterface;
use Akeeba\RemoteCLI\Application\Output\Output;
use Psr\Log\LoggerInterface;

abstract class AbstractCommand implements CommandInterface
{
	/**
	 * The HTTP client the last getApiObject() call created.
	 *
	 * Kept around so a command can report the connection settings autodetect() settled on. The Connector does not
	 * expose the client it was given, and the options are only interesting after autodetect() has rewritten them.
	 *
	 * @var   HttpClientInterface|null
	 * @since 3.2.0
	 */
	protected ?HttpClientInterface $httpClient = null;

	public function __construct(protected Cli $input, protected Output $output, protected LoggerInterface $logger) {}

	public function prepare(): void
	{
		if ($this->input->getBool('m', false))
		{
			$this->input->set('machine-readable', true);
		}

		if ($opt = $this->input->get('h', null, 'raw'))
		{
			$this->input->set('host', $opt);
		}

		if ($opt = $this->input->get('s', null, 'raw'))
		{
			$this->input->set('secret', $opt);
		}

		if ($opt = $this->input->get('t', null, 'raw'))
		{
			$this->input->set('token', $opt);
		}

		/**
		 * The API library spells these options in camelCase. Command line options in this application are spelled in
		 * lower case, so accept the kebab-case spelling a user would expect to type and translate it.
		 */
		if ($opt = $this->input->get('api-version', null, 'raw'))
		{
			$this->input->set('apiVersion', $opt);
		}

		if ($opt = $this->input->get('api-endpoint', null, 'raw'))
		{
			$this->input->set('apiEndpoint', $opt);
		}

		$this->normaliseHostOption();
	}

	/**
	 * Moves the path of Joomla's API application out of the host option and into the apiEndpoint option.
	 *
	 * The JSON API v3 lives in Joomla's API application, at https://www.example.com/api/index.php. That URL is what
	 * a user reaches for when asked which endpoint to connect to, and handing it to us as the host does not work:
	 * the host is meant to be the site's root, and everything below it — the API application included — is worked out
	 * from there. Pasting the API URL would have us look for the API application inside itself, at
	 * .../api/api/index.php, and every candidate would 404 into a bewildering “we cannot find a way to connect”.
	 *
	 * A URL carrying a query string is left alone. The Endpoint URL of the v1 and v2 APIs, which the Schedule
	 * Automatic Backups page still shows, always carries one — and its path really is the site's root plus index.php.
	 *
	 * So is a host given alongside an explicit --api-endpoint. That combination is the escape hatch for the one site
	 * this guess gets wrong: a site which is itself installed in a directory called `api`, where
	 * https://www.example.com/api is the site's root rather than Joomla's API application, and nothing in the URL
	 * says which of the two it is. Such a site connects with
	 * --host=https://www.example.com/api --api-endpoint=api/index.php.
	 *
	 * @return  void
	 * @since   3.2.0
	 */
	private function normaliseHostOption(): void
	{
		$host = $this->getRawOption('host');

		// A query string means a v1 or v2 Endpoint URL, whose path is not the API application's.
		if ($host === '' || str_contains($host, '?'))
		{
			return;
		}

		// The user has told us where the API application is. Take them at their word and leave their host alone.
		if ($this->getRawOption('apiEndpoint') !== '')
		{
			return;
		}

		$hasScheme = (bool) preg_match('#^[a-z][a-z0-9+.\-]*://#i', $host);
		$parts     = parse_url($hasScheme ? $host : 'http://' . ltrim($host, '/'));

		if (!is_array($parts) || empty($parts['host']))
		{
			return;
		}

		$path = trim($parts['path'] ?? '', '/');

		foreach (Options::API_ENDPOINTS as $apiEndpoint)
		{
			if ($path !== $apiEndpoint && !str_ends_with($path, '/' . $apiEndpoint))
			{
				continue;
			}

			$newHost = ($hasScheme ? $parts['scheme'] . '://' : '');

			if (!empty($parts['user']))
			{
				$newHost .= $parts['user'] . (isset($parts['pass']) ? ':' . $parts['pass'] : '') . '@';
			}

			$newHost .= $parts['host'] . (empty($parts['port']) ? '' : ':' . $parts['port']);

			$remainder = $path === $apiEndpoint ? '' : substr($path, 0, -strlen('/' . $apiEndpoint));

			if ($remainder !== '')
			{
				$newHost .= '/' . $remainder;
			}

			$this->input->set('host', $newHost);
			$this->input->set('apiEndpoint', $apiEndpoint);

			$this->logger->debug(
				sprintf(
					'The host you gave me points at Joomla\'s API application. Using ‘%s’ as the site and ‘%s’ as the path to the API application. If your site is in fact installed in a directory called ‘%s’, add --api-endpoint=%s to keep your host as you typed it.',
					$newHost,
					$apiEndpoint,
					strtok($apiEndpoint, '/'),
					Options::DEFAULT_API_ENDPOINT
				)
			);

			return;
		}
	}

	/**
	 * Make sure that the user has provided enough and correct configuration for this command to run. By default we are
	 * only checking that a host name and at least one credential have been provided and are not empty. If the
	 * configuration check fails a suitable exception will be thrown.
	 *
	 * @return  void
	 */
	protected function assertConfigured(): void
	{
		if (empty($this->getRawOption('host')))
		{
			throw new NoConfiguredHost();
		}

		/**
		 * Either credential will do on its own. The JSON API v3 authenticates with a Joomla! API token, which is what
		 * we would rather use; the older API versions only understand the Secret Word.
		 */
		if (empty($this->getRawOption('secret')) && empty($this->getRawOption('token')))
		{
			throw new NoConfiguredSecret();
		}
	}

	/**
	 * Return API options based on the command line parameters and the additional options defined programmatically.
	 *
	 * @param   array  $additional  Any additional parameters you are defining.
	 *
	 * @return  Options
	 */
	protected function getApiOptions(array $additional = []): Options
	{
		$options = array_replace_recursive($this->input->getData(), [
			'capath' => AKEEBA_CACERT_PEM,
		], $additional);

		// It's handled in the remote.php entry point.
		unset($options['certificate']);

		return new Options($options, false);
	}

	protected function getApiObject(array $additional = []): Connector
	{
		$additional = array_merge([
			'logger' => $this->logger,
		], $additional);
		$options    = $this->getApiOptions($additional);

		$this->httpClient = new HttpClientJoomla($options);
		$api              = new Connector($this->httpClient);

		$api->autodetect();

		return $api;
	}

	/**
	 * Returns the connection options autodetect() settled on, if we have connected to a site at all.
	 *
	 * @return  Options|null
	 * @since   3.2.0
	 */
	protected function getNegotiatedOptions(): ?Options
	{
		return $this->httpClient?->getOptions();
	}

	protected function getDownloadOptions(): DownloadOptions
	{
		return new DownloadOptions([
			'mode'      => $this->input->getCmd('dlmode', 'http'),
			'path'      => $this->input->getPath('dlpath', getcwd()),
			'id'        => $this->input->getInt('id', 0),
			'filename'  => $this->input->getString('', ''),
			'delete'    => $this->input->getBool('delete', false),
			'part'      => $this->input->getInt('part', -1),
			'chunkSize' => $this->input->getInt('chunk_size', 0),
			'url'       => $this->input->getString('dlurl', ''),
		]);
	}

	/**
	 * Reads a command line option without any filtering, other than trimming whitespace.
	 *
	 * Credentials must not be filtered. A Joomla! API token is Base64 and ends in one or two equals signs; the `cmd`
	 * filter this used to go through would eat them, and a partially eaten credential is worse than no credential at
	 * all — it turns "you did not give me a token" into an authentication failure the user cannot explain.
	 *
	 * @param   string  $name  The name of the option to read
	 *
	 * @return  string
	 * @since   3.2.0
	 */
	private function getRawOption(string $name): string
	{
		$value = $this->input->get($name, '', 'raw');

		// An option given with no value at all, e.g. `--token` on its own, parses as boolean true.
		if (!is_scalar($value) || is_bool($value))
		{
			return '';
		}

		return trim((string) $value);
	}
}
