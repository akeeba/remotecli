<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Model;


use Akeeba\RemoteCLI\Api\Api;
use Akeeba\RemoteCLI\Api\Options;
use Akeeba\RemoteCLI\Exception\CannotListProfiles;
use Akeeba\RemoteCLI\Exception\NoProfileData;
use Akeeba\RemoteCLI\Exception\NoProfileID;
use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Output\Output;

class Profiles
{
	/**
	 * Return a list of backup profiles
	 *
	 * @param   Cli      $input    The input object.
	 * @param   Output   $output   The output object.
	 * @param   Options  $options  The API options. The format, verb and endpoint options _may_ be overwritten.
	 *
	 * @return  array
	 */
	public function getProfiles(Cli $input, Output $output, Options $options)
	{
		$api = new Api($options, $output);

		$data = $api->doQuery('getProfiles');

		if ($data->body->status != 200)
		{
			throw new CannotListProfiles();
		}

		return $data->body->data;
	}

	public function exportConfiguration(Cli $input, Output $output, Options $options)
	{
		$id = $input->getInt('id', -1);

		if ($id <= 0)
		{
			throw new NoProfileID();
		}

		$api = new Api($options, $output);

		$data = $api->doQuery('exportConfiguration', ['profile' => $input->getInt('id')]);

		return $data->body->data;
	}

	public function importConfiguration(Cli $input, Output $output, Options $options)
	{
		$data = $input->get('data', '', 'raw');

		if (!$data)
		{
			throw new NoProfileData();
		}

		$data = json_decode($data);

		$api = new Api($options, $output);

		$response = $api->doQuery('importConfiguration', ['profile' => 0, 'data' => $data]);

		return $response->body->data;
	}
}
