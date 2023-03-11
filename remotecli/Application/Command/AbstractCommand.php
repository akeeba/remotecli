<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\DataShape\DownloadOptions;
use Akeeba\RemoteCLI\Api\Exception\NoConfiguredHost;
use Akeeba\RemoteCLI\Api\Exception\NoConfiguredSecret;
use Akeeba\RemoteCLI\Api\Options;
use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Kernel\CommandInterface;
use Akeeba\RemoteCLI\Application\Output\Output;
use Psr\Log\LoggerInterface;

abstract class AbstractCommand implements CommandInterface
{
	private LoggerInterface $logger;

	public function prepare(Cli $input): void
	{
		if ($input->getBool('m', false))
		{
			$input->set('machine-readable', true);
		}

		if ($opt = $input->get('h', null, 'raw'))
		{
			$input->set('host', $opt);
		}

		if ($opt = $input->get('s', null, 'raw'))
		{
			$input->set('secret', $opt);
		}
	}

	public function setLogger(LoggerInterface $logger): self
	{
		$this->logger = $logger;

		return $this;
	}

	/**
	 * Make sure that the user has provided enough and correct configuration for this command to run. By default we are
	 * only checking that a host name and a secret key have been provided and are not empty. If the configuration check
	 * fails a suitable exception will be thrown.
	 *
	 * @param   Cli  $input  The input object.
	 *
	 * @return  void
	 */
	protected function assertConfigured(Cli $input): void
	{
		if (empty($input->getCmd('host', null)))
		{
			throw new NoConfiguredHost();
		}

		if (empty($input->getCmd('secret', null)))
		{
			throw new NoConfiguredSecret();
		}
	}

	/**
	 * Return API options based on the command line parameters and the additional options defined programmatically.
	 *
	 * @param   Cli    $input       The input object.
	 * @param   array  $additional  Any additional parameters you are defining.
	 *
	 * @return  Options
	 */
	protected function getApiOptions(Cli $input, array $additional = []): Options
	{
		$options = array_replace_recursive($input->getData(), [
			'capath' => AKEEBA_CACERT_PEM,
		], $additional);

		// It's handled in the remote.php entry point.
		unset($options['certificate']);

		return new Options($options, false);
	}

	protected function getApiObject(Cli $input, Output $output, array $additional = []): Connector
	{
		$additional = array_merge([
			'logger' => $this->logger,
		], $additional);
		$options    = $this->getApiOptions($input, $additional);

		$api = new Connector($options);

		$api->autodetect();

		return $api;
	}

	protected function getDownloadOptions(Cli $input): DownloadOptions
	{
		return new DownloadOptions([
			'mode'      => $input->getCmd('dlmode', 'http'),
			'path'      => $input->getPath('dlpath', getcwd()),
			'id'        => $input->getInt('id', 0),
			'filename'  => $input->getString('', ''),
			'delete'    => $input->getBool('delete', false),
			'part'      => $input->getInt('part', -1),
			'chunkSize' => $input->getInt('chunk_size', 0),
			'url'       => $input->getString('dlurl', ''),
		]);
	}
}
