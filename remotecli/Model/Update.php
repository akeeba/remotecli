<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Model;


use Akeeba\RemoteCLI\Api\Api;
use Akeeba\RemoteCLI\Api\Options;
use Akeeba\RemoteCLI\Exception\CannotGetUpdateInformation;
use Akeeba\RemoteCLI\Exception\CannotListBackupRecords;
use Akeeba\RemoteCLI\Exception\LiveUpdateCleanupError;
use Akeeba\RemoteCLI\Exception\LiveUpdateDownloadError;
use Akeeba\RemoteCLI\Exception\LiveUpdateExtractError;
use Akeeba\RemoteCLI\Exception\LiveUpdateInstallError;
use Akeeba\RemoteCLI\Exception\LiveUpdateStuck;
use Akeeba\RemoteCLI\Exception\LiveUpdateSupport;
use Akeeba\RemoteCLI\Exception\NoUpdates;
use Akeeba\RemoteCLI\Exception\RemoteError;
use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Output\Output;

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
		$api = new Api($options, $output);

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
		$api = new Api($options, $output);

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
		$api = new Api($options, $output);

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
		$api = new Api($options, $output);

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
		$api = new Api($options, $output);

		$data = $api->doQuery('updateCleanup', array());

		if ($data->body->status != 200)
		{
			throw new LiveUpdateCleanupError($data->body->data);
		}
	}

}
