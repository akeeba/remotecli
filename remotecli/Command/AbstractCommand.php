<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Command;


use Akeeba\RemoteCLI\Api\Options;
use Akeeba\RemoteCLI\Exception\NoConfiguredHost;
use Akeeba\RemoteCLI\Exception\NoConfiguredSecret;
use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Kernel\CommandInterface;

abstract class AbstractCommand implements CommandInterface
{
	/**
	 * Make sure that the user has provided enough and correct configuration for this command to run. By default we are
	 * only checking that a host name and a secret key have been provided and are not empty. If the configuration check
	 * fails a suitable exception will be thrown.
	 *
	 * @param   Cli  $input  The input object.
	 *
	 * @return  void
	 */
	protected function assertConfigured(Cli $input)
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
	protected function getApiOptions(Cli $input, array $additional = [])
	{
		$options = array_replace_recursive($input->getData(), $additional);

		return new Options($options, false);
	}
}
