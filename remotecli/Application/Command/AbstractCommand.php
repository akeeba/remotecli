<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

use Akeeba\BackupJsonApi\Connector;
use Akeeba\BackupJsonApi\DataShape\DownloadOptions;
use Akeeba\BackupJsonApi\Exception\NoConfiguredHost;
use Akeeba\BackupJsonApi\Exception\NoConfiguredSecret;
use Akeeba\BackupJsonApi\HttpAbstraction\HttpClientJoomla;
use Akeeba\BackupJsonApi\Options;
use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Kernel\CommandInterface;
use Akeeba\RemoteCLI\Application\Output\Output;
use Psr\Log\LoggerInterface;

abstract class AbstractCommand implements CommandInterface
{
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
	}

	/**
	 * Make sure that the user has provided enough and correct configuration for this command to run. By default we are
	 * only checking that a host name and a secret key have been provided and are not empty. If the configuration check
	 * fails a suitable exception will be thrown.
	 *
	 * @return  void
	 */
	protected function assertConfigured(): void
	{
		if (empty($this->input->getCmd('host', null)))
		{
			throw new NoConfiguredHost();
		}

		if (empty($this->input->getCmd('secret', null)))
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

		$httpClient = new HttpClientJoomla($options);
		$api        = new Connector($httpClient);

		$api->autodetect();

		return $api;
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
}
