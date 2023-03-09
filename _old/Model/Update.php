<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\OLD\RemoteCLI\Model;


use Akeeba\OLD\RemoteCLI\Exception\CannotGetUpdateInformation;
use Akeeba\OLD\RemoteCLI\Exception\LiveUpdateCleanupError;
use Akeeba\OLD\RemoteCLI\Exception\LiveUpdateDownloadError;
use Akeeba\OLD\RemoteCLI\Exception\LiveUpdateExtractError;
use Akeeba\OLD\RemoteCLI\Exception\LiveUpdateInstallError;
use Akeeba\OLD\RemoteCLI\Exception\LiveUpdateStuck;
use Akeeba\OLD\RemoteCLI\Exception\LiveUpdateSupport;
use Akeeba\OLD\RemoteCLI\Exception\NoUpdates;
use Akeeba\OLD\RemoteCLI\Input\Cli;
use Akeeba\OLD\RemoteCLI\Output\Output;
use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\Options;

class Update
{
	/**
	 * REturn the update information from the remote site
	 *
	 * @param   Cli      $input    The input object.
	 * @param   Output   $output   The output object.
	 * @param   Options  $options  The API options. The format, verb and endpoint options _may_ be overwritten.
	 *
	 * @return  object
	 */
	public function getUpdateInformation(Cli $input, Output $output, Options $options)
	{
		$api = new Connector($options, $output);

		$force        = $input->getBool('force', false);

		$data = $api->doQuery('updateGetInformation', array('force' => $force));

		if ($data->body->status != 200)
		{
			throw new CannotGetUpdateInformation();
		}

		// Is it supported?
		$updateInfo = $data->body->data;

		if ( !$updateInfo->supported)
		{
			throw new LiveUpdateSupport();
		}

		// Is it stuck?
		if ($updateInfo->stuck)
		{
			throw new LiveUpdateStuck($force ? '' : 'Try using the command line parameter --force=1');
		}

		// Do we have updates?
		if ( !$updateInfo->hasUpdates)
		{
			throw new NoUpdates();
		}

		return $updateInfo;
	}

	/**
	 * Download the update
	 *
	 * @param   Cli      $input    The input object.
	 * @param   Output   $output   The output object.
	 * @param   Options  $options  The API options. The format, verb and endpoint options _may_ be overwritten.
	 *
	 * @return  void
	 */
	public function downloadUpdate(Cli $input, Output $output, Options $options)
	{
		$api = new Connector($options, $output);

		$data = $api->doQuery('updateDownload', array());

		if ($data->body->status != 200)
		{
			throw new LiveUpdateDownloadError($data->body->data);
		}
	}

	/**
	 * Extract the update
	 *
	 * @param   Cli      $input    The input object.
	 * @param   Output   $output   The output object.
	 * @param   Options  $options  The API options. The format, verb and endpoint options _may_ be overwritten.
	 *
	 * @return  void
	 */
	public function extractUpdate(Cli $input, Output $output, Options $options)
	{
		$api = new Connector($options, $output);

		$data = $api->doQuery('updateExtract', array());

		if ($data->body->status != 200)
		{
			throw new LiveUpdateExtractError($data->body->data);
		}
	}

	/**
	 * Install the update
	 *
	 * @param   Cli      $input    The input object.
	 * @param   Output   $output   The output object.
	 * @param   Options  $options  The API options. The format, verb and endpoint options _may_ be overwritten.
	 *
	 * @return  void
	 */
	public function installUpdate(Cli $input, Output $output, Options $options)
	{
		$api = new Connector($options, $output);

		$data = $api->doQuery('updateInstall', array());

		if ($data->body->status != 200)
		{
			throw new LiveUpdateInstallError($data->body->data);
		}
	}

	/**
	 * Clean up after the update
	 *
	 * @param   Cli      $input    The input object.
	 * @param   Output   $output   The output object.
	 * @param   Options  $options  The API options. The format, verb and endpoint options _may_ be overwritten.
	 *
	 * @return  void
	 */
	public function cleanupUpdate(Cli $input, Output $output, Options $options)
	{
		$api = new Connector($options, $output);

		$data = $api->doQuery('updateCleanup', array());

		if ($data->body->status != 200)
		{
			throw new LiveUpdateCleanupError($data->body->data);
		}
	}

}
