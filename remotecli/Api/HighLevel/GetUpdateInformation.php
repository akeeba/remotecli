<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\HighLevel;

use Akeeba\RemoteCLI\Api\Connector;
use Akeeba\RemoteCLI\Api\Exception\CannotGetUpdateInformation;
use Akeeba\RemoteCLI\Api\Exception\LiveUpdateStuck;
use Akeeba\RemoteCLI\Api\Exception\LiveUpdateSupport;
use Akeeba\RemoteCLI\Api\Exception\NoUpdates;

class GetUpdateInformation
{
	public function __construct(private Connector $connector)
	{
	}

	public function __invoke(bool $force = false): object
	{
		$data = $this->connector->doQuery('updateGetInformation', array('force' => $force));

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
}
