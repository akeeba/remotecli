<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\OLD\RemoteCLI\Command;


use Akeeba\OLD\RemoteCLI\Input\Cli;
use Akeeba\OLD\RemoteCLI\Kernel\CommandInterface;
use Akeeba\RemoteCLI\Api\Exception\NoConfiguredHost;
use Akeeba\RemoteCLI\Api\Exception\NoConfiguredSecret;
use Akeeba\RemoteCLI\Api\Options;

abstract class AbstractCommand implements CommandInterface
{
	public function prepare(Cli $input): void
	{
		if ($input->getBool('m', false))
		{
			$input->set('machine-readable', true);
		}

		if ($opt = $input->get('h', null))
		{
			$input->set('host', $opt);
		}

		if ($opt = $input->get('s', null))
		{
			$input->set('secret', $opt);
		}
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
		$options = array_replace_recursive($input->getData(), $additional);

		return new Options($options, false);
	}
}
