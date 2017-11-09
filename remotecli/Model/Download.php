<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2006-2017 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Model;


use Akeeba\RemoteCLI\Api\Api;
use Akeeba\RemoteCLI\Api\Options;
use Akeeba\RemoteCLI\Exception\NoBackupID;
use Akeeba\RemoteCLI\Exception\NoDownloadMode;
use Akeeba\RemoteCLI\Exception\NoDownloadPath;
use Akeeba\RemoteCLI\Exception\NoDownloadURL;
use Akeeba\RemoteCLI\Exception\RemoteError;
use Akeeba\RemoteCLI\Input\Cli;
use Akeeba\RemoteCLI\Output\Output;

class Download
{
	/**
	 * Validations the input and returns an array of download parameters
	 *
	 * @param   Cli  $input  The user input
	 *
	 * @return  array
	 */
	public function getValidatedParameters(Cli $input)
	{
		$parameters = [
			'mode'     => strtolower($input->getCmd('dlmode', 'http')),
			'path'     => $input->getPath('dlpath', getcwd()),
			'id'       => $input->getInt('id', 0),
			'filename' => $input->getPath('filename', ''),
			'delete'   => $input->getBool('delete', false),
		];

		if (!in_array($parameters['mode'], array('http', 'curl', 'chunk')))
		{
			throw new NoDownloadMode();
		}

		if (empty($parameters['path']) || !is_dir($parameters['path']))
		{
			throw new NoDownloadPath();
		}

		switch ($parameters['mode'])
		{
			case 'http':
				break;

			case 'chunk':
				$parameters['chunkSize'] = $input->getInt('chunk_size', 1);

				if ($parameters['chunkSize'] <= 1)
				{
					$parameters['chunkSize'] = 1;
				}
				break;

			case 'curl':
				$parameters['url'] = $input->get('dlurl', '', 'raw');
				$parameters['url'] = rtrim($parameters['url'], '/');

				if (empty($parameters['url']))
				{
					throw new NoDownloadURL();
				}

				list($parameters['url'], $parameters['authentication']) = $this->processAuthenticatedUrl($parameters['url']);
				break;
		}

		return $parameters;
	}

	/**
	 * Download a backup archive. If
	 *
	 * @param   array    $params   Download parameters
	 * @param   Output   $output   Output handler
	 * @param   Options  $options  API options
	 */
	public function download(array $params, Output $output, Options $options)
	{
		/**
		 * We check the Download ID late in the process since using backup + download means that we do not have access
		 * to the ID until we finish the backup. However, we have to make sure that the download information is correct
		 * before we take the backup to prevent wasting our time.
		 */
		if (($params['id'] <= 0))
		{
			throw new NoBackupID();
		}

		switch ($params['mode'])
		{
			case 'http':
				$this->downloadHTTP($params, $output, $options);
				break;

			case 'chunk':
				$this->downloadChunk($params, $output, $options);
				break;

			case 'curl':
				$this->downloadCURL($params, $output, $options);
				break;
		}

		// Do I also have to delete the files after I download them?
		if ($params['delete'])
		{
			$this->deleteFiles($params, $output, $options);
		}
	}

	/**
	 * Process a URL, extracting its authentication part as a separate string. Used for downloading with cURL.
	 *
	 * @param   string  $url  The URL to process e.g. "ftp://user:password@ftp.example.com/path/to/file.jpa"
	 *
	 * @return  array  [$url, $authentication]
	 */
	private function processAuthenticatedUrl($url)
	{
		$url                 = rtrim($url, '/');
		$authentication      = '';
		$doubleSlashPosition = strpos($url, '//');

		if ($doubleSlashPosition == false)
		{
			return array($url, $authentication);
		}

		$offset         = $doubleSlashPosition + 2;
		$atSignPosition = strpos($url, '@', $offset);
		$colonPosition  = strpos($url, ':', $offset);

		if (($colonPosition === false) || ($atSignPosition === false))
		{
			return array($url, $authentication);
		}

		$offset = $colonPosition + 1;

		while ($atSignPosition !== false)
		{
			$atSignPosition = strpos($url, '@', $offset);

			if ($atSignPosition !== false)
			{
				$offset = $atSignPosition + 1;
			}
		}

		$atSignPosition = $offset - 1;
		$authentication = substr($url, $doubleSlashPosition + 2, $atSignPosition - $doubleSlashPosition - 2);
		$protocol       = substr($url, 0, $doubleSlashPosition + 2);
		$restOfURL      = substr($url, $atSignPosition + 1);
		$url            = $protocol . $restOfURL;

		return array($url, $authentication);
	}

	private function downloadHTTP(array $options, Output $output, Options $options)
	{
		// TODO
	}

	private function downloadChunk(array $options, Output $output, Options $options)
	{
		// TODO
	}

	private function downloadCURL(array $options, Output $output, Options $options)
	{
		// TODO
	}

	private function deleteFiles(array $options, Output $output, Options $options)
	{
		// TODO
	}
}
