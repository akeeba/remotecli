<?php

/**
 * @package   AkeebaRemote
 * @copyright Copyright (c)2009-2016 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 * @version   $Id$
 */
class RemoteAppController
{
	/** @var Which action to execute */
	private $_action = '';

	/** @var Remote site's version information */
	private $_versionInfo = null;

	/**
	 * Sets the action to be executed
	 *
	 * @param string $action
	 */
	public function setAction($action)
	{
		$this->_action = $action;
	}

	/**
	 * Executes the selected action
	 */
	public function execute()
	{
		if (method_exists($this, $this->_action))
		{
			$action = $this->_action;
			$this->$action();
		}
		else
		{
			throw new RemoteAppExceptionAction("Invalid action {$this->_action}");
		}
	}

	/**
	 * Test the connection to the Akeeba Backup component installed on your
	 * remote site.
	 */
	private function test($component = 'com_akeeba', $force = true)
	{
		$options = RemoteUtilsCli::getInstance();
		$host    = $options->host;
		$secret  = $options->secret;

		if (empty($host))
		{
			throw new RemoteAppExceptionHost;
		}
		if (empty($secret))
		{
			throw new RemoteAppExceptionSecret;
		}

		$api         = RemoteApi::getInstance();
		$api->host   = $host;
		$api->secret = $secret;

		$works     = false;
		$exception = null;

		foreach (array('GET', 'POST') as $verb)
		{
			if ($works)
			{
				break;
			}

			$api->verb = $verb;

			foreach (array('raw', 'html') as $format)
			{
				if ($works)
				{
					break;
				}

				$api->format = $format;

				try
				{
					$ret                = $api->doQuery('getVersion', array(), $component);
					$works              = true;
					$this->_versionInfo = $ret->body->data;
				}
				catch (RemoteException $e)
				{
					$exception = $e;
				}
			}
		}

		if ( !$works)
		{
			throw new RemoteAppExceptionNoway();
		}
		else
		{
			// Check the response
			if ($ret->body->status != 200)
			{
				throw new RemoteExceptionError('Error ' . $ret->body->status . " - " . $ret->body->data);
			}

			// Check the API version
			if ($ret->body->data->api < ARCCLI_MINAPI)
			{
				throw new RemoteExceptionVersion();
			}

			RemoteUtilsRender::info('Successful connection to site', $force);
			RemoteUtilsRender::info('', $force);
		}
	}

	/**
	 * Performs a site backup
	 */
	private function backup()
	{
		$options     = RemoteUtilsCli::getInstance();
		$profile     = (int)($options->get('profile', 1));
		$description = $options->get('description', "Remote backup");
		$comment     = $options->get('comment', '');
		$backupId    = 0;
		$archive     = '';
		$progress    = 0;

		// @todo Test for download options

		// Determine which way to contact the server
		$this->test('com_akeeba', false);

		$api = RemoteApi::getInstance();

		$config = array(
			'profile'     => $profile,
			'description' => $description,
			'comment'     => $comment
		);
		$data   = $api->doQuery('startBackup', $config);

		if ($data->body->status != 200)
		{
			throw new RemoteException('Error ' . $data->body->status . ": " . $data->body->data);
		}

		while ($data->body->data->HasRun)
		{
			if (isset($data->body->data->BackupID))
			{
				$backupId = $data->body->data->BackupID;
			}

			if (isset($data->body->data->Archive))
			{
				$archive = $data->body->data->Archive;
			}

			if (isset($data->body->data->Progress))
			{
				$progress = $data->body->data->Progress;
			}

			RemoteUtilsRender::header('Got backup tick');
			RemoteUtilsRender::info("Progress: {$progress}%");
			RemoteUtilsRender::info("Domain  : {$data->body->data->Domain}");
			RemoteUtilsRender::info("Step    : {$data->body->data->Step}");
			RemoteUtilsRender::info("Substep : {$data->body->data->Substep}");
			if ( !empty($data->body->data->Warnings))
			{
				foreach ($data->body->data->Warnings as $warning)
				{
					RemoteUtilsRender::warning("Warning : $warning");
				}
			}
			RemoteUtilsRender::info('');

			$data = $api->doQuery('stepBackup');
			if ($data->body->status != 200)
			{
				throw new RemoteExceptionError('Error ' . $data->body->status . ": " . $data->body->data);
			}
		}

		RemoteUtilsRender::header('Backup finished successfully');

		// If we have to download, proceed with the download step
		if ($options->download && $backupId)
		{
			$options->set('id', $backupId);
			$options->set('archive', $archive);
			$this->download();
		}
	}

	/**
	 * Downloads a backup archive to disk
	 *
	 * @param bool $onlyCheck When true, only the preliminary check of parameters is performed
	 */
	private function download($onlyCheck = false)
	{
		$options = RemoteUtilsCli::getInstance();
		$dlmode  = $options->get('dlmode', '');
		$path    = $options->get('dlpath', '');
		$id      = (int)($options->get('id', 0));

		$filename = $options->get('filename', '');

		if ( !in_array($dlmode, array('http', 'curl', 'chunk')))
		{
			throw new RemoteAppExceptionDlmode();
		}

		if (empty($path) || !is_dir($path))
		{
			throw new RemoteAppExceptionDlpath();
		}

		if (($id <= 0) && !$onlyCheck)
		{
			throw new RemoteAppExceptionBackupid();
		}

		if ( !$onlyCheck)
		{
			$api = RemoteApi::getInstance();
			if ( !$api->isConfigured())
			{
				$this->test('com_akeeba', false);
			}
		}

		$method = '_download' . ucfirst($dlmode);

		$this->$method($onlyCheck, $filename);

		if ($options->delete)
		{
			$this->deletefiles();
		}
	}

	/**
	 * Performs a backup download through HTTP (as one file per part)
	 *
	 * @param bool $onlyCheck When true, only the preliminary check of parameters is performed
	 */
	private function _downloadHttp($onlyCheck = false, $filename = '')
	{
		if ($onlyCheck)
		{
			return;
		}

		$api     = RemoteApi::getInstance();
		$options = RemoteUtilsCli::getInstance();

		$id   = $options->id;
		$path = rtrim($options->dlpath, '/' . DIRECTORY_SEPARATOR);

		// Get the backup info
		$data     = $api->doQuery('getBackupInfo', array('backup_id' => $id));
		$parts    = $data->body->data->multipart;
		$filedefs = $data->body->data->filenames;
		$filedata = array();
		foreach ($filedefs as $def)
		{
			$filedata[$def->part] = (object)array('name' => $def->name, 'size' => $def->size);
		}
		if ($parts <= 0)
		{
			$parts = 1;
		}

		if ( !count($filedefs))
		{
			throw new RemoteExceptionNofiles();
		}

		for ($part = 1; $part <= $parts; $part++)
		{
			// Open file pointer
			$name     = $filedata[$part]->name;
			$size     = $filedata[$part]->size;
			$fullpath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($fullpath, 'wb');

			if ($fp == false)
			{
				throw new RemoteExceptionFilewrite("Could not open $fullpath for writing");
			}

			// Get the signed URL
			$url = $api->getURL() . '?' . $api->prepareQuery('downloadDirect', array('backup_id' => $id, 'part_id' => $part));

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
			//curl_setopt($ch, CURLOPT_TIMEOUT, 180);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			// Pretend we are Firefox, so that webservers play nice with us
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0.1) Gecko/20110506 Firefox/4.0.1');
			$status = curl_exec($ch);
			@fclose($fp);
			$errno      = curl_errno($ch);
			$errmessage = curl_error($ch);
			curl_close($ch);

			if ($status === false)
			{
				throw new RemoteExceptionCurl("Could not download $fullpath -- $errno : $errmessage");
			}

			// Check file size
			$sizematch = true;
			clearstatcache();
			$filesize = @filesize($fullpath);
			if ($filesize !== false)
			{
				if ($filesize != $size)
				{
					RemoteUtilsRender::warning("Filesize mismatch on $fullpath");
					$sizematch = false;
				}
			}

			if ($sizematch)
			{
				// try renaming
				if (strlen($filename))
				{
					@rename($fullpath, $path . DIRECTORY_SEPARATOR . $filename);
					if (file_exists($path . DIRECTORY_SEPARATOR . $filename))
					{
						RemoteUtilsRender::info("Successfully renamed $name to $filename");
					}
					else
					{
						RemoteUtilsRender::info("Failed to rename $name to $filename");
					}
				}
				RemoteUtilsRender::info("Successfully downloaded $name", true);
			}
		}

		RemoteUtilsRender::header("Archive downloaded successfully");
	}

	/**
	 * Downloads a backup archive in chunked mode. WARNING: THIS DOES NOT WORK
	 * CORRECTLY.
	 *
	 * @param bool $onlyCheck When true, only the preliminary check of parameters is performed
	 */
	private function _downloadChunk($onlyCheck = false, $filename = '')
	{
		if ($onlyCheck)
		{
			return;
		}

		$api     = RemoteApi::getInstance();
		$options = RemoteUtilsCli::getInstance();

		$id   = $options->id;
		$path = rtrim($options->dlpath, '/' . DIRECTORY_SEPARATOR);

		// Now chunk Size can be defined with apis
		$chunk_size = $options->get('chunk_size', '1');

		// Get the backup info
		$data     = $api->doQuery('getBackupInfo', array('backup_id' => $id));
		$parts    = $data->body->data->multipart;
		$filedefs = $data->body->data->filenames;
		$filedata = array();
		foreach ($filedefs as $def)
		{
			$filedata[$def->part] = (object)array('name' => $def->name, 'size' => $def->size);
		}
		if ($parts <= 0)
		{
			$parts = 1;
		}

		if ( !count($filedefs))
		{
			throw new RemoteExceptionNofiles();
		}

		for ($part = 1; $part <= $parts; $part++)
		{
			// Open file pointer
			$name     = $filedata[$part]->name;
			$size     = $filedata[$part]->size;
			$fullpath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($fullpath, 'wb');

			if ($fp == false)
			{
				throw new RemoteExceptionFilewrite("Could not open $fullpath for writing");
			}

			$frag = 0;
			$done = false;
			while ( !$done)
			{
				$frag++;
				$data = $api->doQuery('download', array(
					'backup_id'  => $id,
					'part'       => $part,
					'segment'    => $frag,
					'chunk_size' => $chunk_size
				));

				switch ($data->body->status)
				{
					case 200:
						$rawData = base64_decode($data->body->data);
						$len     = strlen($rawData); //echo "\tWriting $len bytes\n";
						fwrite($fp, $rawData);
						unset($rawData);
						unset($data);
						break;

					case 404:
						if ($frag == 1)
						{
							throw new RemoteExceptionNofiles("Could not download $fullpath -- 404 : Not Found");
						}
						else
						{
							$done = true;
						}
						break;

					default:
						throw new RemoteExceptionNofiles("Could not download $fullpath -- {$data->body->status} : {$data->body->data}");
						break;
				}
			}

			@fclose($fp);

			// Check file size
			$sizematch = true;
			clearstatcache();
			$filesize = @filesize($fullpath);
			if ($filesize !== false)
			{
				if ($filesize != $size)
				{
					RemoteUtilsRender::warning("Filesize mismatch on $fullpath");
					$sizematch = false;
				}
			}

			if ($sizematch)
			{
				// try renaming
				if (strlen($filename))
				{
					@rename($fullpath, $path . DIRECTORY_SEPARATOR . $filename);
					if (file_exists($path . DIRECTORY_SEPARATOR . $filename))
					{
						RemoteUtilsRender::info("Successfully renamed $name to $filename");
					}
					else
					{
						RemoteUtilsRender::info("Failed to rename $name to $filename");
					}
				}
				RemoteUtilsRender::info("Successfully downloaded $name", true);
			}
		}
	}

	/**
	 * Performs a backup download using cURL
	 *
	 * @param bool $onlyCheck When true, only the preliminary check of parameters is performed
	 */
	private function _downloadCurl($onlyCheck = false, $filename = '')
	{
		$options = RemoteUtilsCli::getInstance();
		$api     = RemoteApi::getInstance();

		$dlurl = $options->get('dlurl', '');

		if (empty($dlurl))
		{
			throw new RemoteAppExceptionDlurl;
		}

		if ($onlyCheck)
		{
			return;
		}

		$id    = $options->id;
		$path  = rtrim($options->dlpath, '/' . DIRECTORY_SEPARATOR);
		$dlurl = rtrim($options->dlurl, '/');

		$authentication = '';

		$doubleSlash = strpos($dlurl, '//');
		if ($doubleSlash != false)
		{
			$offset = $doubleSlash + 2;
			$atSign = strpos($dlurl, '@', $offset);
			$colon  = strpos($dlurl, ':', $offset);
			if (($colon !== false) && ($atSign !== false))
			{
				$offset = $colon + 1;
				while ($atSign !== false)
				{
					$atSign = strpos($dlurl, '@', $offset);
					if ($atSign !== false)
					{
						$offset = $atSign + 1;
					}
				}
				$atSign = $offset - 1;

				$authentication = substr($dlurl, $doubleSlash + 2, $atSign - $doubleSlash - 2);
				$protocol       = substr($dlurl, 0, $doubleSlash + 2);
				$restOfURL      = substr($dlurl, $atSign + 1);
				$dlurl          = $protocol . $restOfURL;
			}
		}

		// Get the backup info
		$data     = $api->doQuery('getBackupInfo', array('backup_id' => $id));
		$parts    = $data->body->data->multipart;
		$filedefs = $data->body->data->filenames;
		$filedata = array();
		foreach ($filedefs as $def)
		{
			$filedata[$def->part] = (object)array('name' => $def->name, 'size' => $def->size);
		}
		if ($parts <= 0)
		{
			$parts = 1;
		}

		if ( !count($filedefs))
		{
			throw new RemoteExceptionNofiles();
		}

		for ($part = 1; $part <= $parts; $part++)
		{
			// Open file pointer
			$name     = $filedata[$part]->name;
			$size     = $filedata[$part]->size;
			$fullpath = $path . DIRECTORY_SEPARATOR . $name;
			$fp       = @fopen($fullpath, 'wb');

			if ($fp == false)
			{
				throw new RemoteExceptionFilewrite("Could not open $fullpath for writing");
			}

			// Get the target path
			$url = $dlurl . '/' . $name;

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
			//curl_setopt($ch, CURLOPT_TIMEOUT, 180);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0.1) Gecko/20110506 Firefox/4.0.1');
			if ( !empty($authentication))
			{
				curl_setopt($ch, CURLOPT_USERPWD, $authentication);
			}
			$status = curl_exec($ch);
			@fclose($fp);
			$errno      = curl_errno($ch);
			$errmessage = curl_error($ch);
			curl_close($ch);

			if ($status === false)
			{
				throw new RemoteExceptionNofiles("Could not download $fullpath -- $errno : $errmessage");
			}

			// Check file size
			$sizematch = true;
			clearstatcache();
			$filesize = @filesize($fullpath);
			if ($filesize !== false)
			{
				if ($filesize != $size)
				{
					RemoteUtilsRender::warning("Filesize mismatch on $fullpath");
					$sizematch = false;
				}
			}

			if ($sizematch)
			{
				// try renaming
				if (strlen($filename))
				{
					@rename($fullpath, $path . DIRECTORY_SEPARATOR . $filename);
					if (file_exists($path . DIRECTORY_SEPARATOR . $filename))
					{
						RemoteUtilsRender::info("Successfully renamed $name to $filename");
					}
					else
					{
						RemoteUtilsRender::info("Failed to rename $name to $filename");
					}
				}
				RemoteUtilsRender::info("Successfully downloaded $name", true);
			}
		}

		RemoteUtilsRender::header("Archive downloaded successfully");
	}

	/**
	 * Deletes the files associated with a backup record, but not the backup
	 * record itself
	 */
	private function deletefiles()
	{
		$options = RemoteUtilsCli::getInstance();
		$api     = RemoteApi::getInstance();

		$id = (int)($options->get('id', 0));

		if ($id <= 0)
		{
			throw new RemoteAppExceptionBackupid();
		}

		if ( !$api->isConfigured())
		{
			$this->test('com_akeeba', false);
		}

		$data = $api->doQuery('deleteFiles', array('backup_id' => $id));

		if ($data->body->status != 200)
		{
			throw new RemoteExceptionCantdelete("Could not delete files of backup record $id -- {$data->body->status} : {$data->body->data}");
		}

		RemoteUtilsRender::header("Files of backup record $id were successfully deleted");
	}

	/**
	 * Deletes a backup record and its associated files
	 */
	private function delete()
	{
		$options = RemoteUtilsCli::getInstance();
		$api     = RemoteApi::getInstance();

		$id = (int)($options->get('id', 0));

		if ($id <= 0)
		{
			throw new RemoteAppExceptionBackupid();
		}

		if ( !$api->isConfigured())
		{
			$this->test('com_akeeba', false);
		}

		$data = $api->doQuery('delete', array('backup_id' => $id));

		if ($data->body->status != 200)
		{
			throw new RemoteExceptionCantdeleterecord("Could not delete backup record $id -- {$data->body->status} : {$data->body->data}");
		}

		RemoteUtilsRender::header("Backup record $id were successfully deleted");
	}

	/**
	 * Produces a list of available profiles
	 */
	private function profiles()
	{
		$options = RemoteUtilsCli::getInstance();
		$api     = RemoteApi::getInstance();

		$this->test('com_akeeba', false);

		$data = $api->doQuery('getProfiles', array());

		if ($data->body->status != 200)
		{
			throw new RemoteExceptionCantlistprofiles("Could not list profiles");
		}

		RemoteUtilsRender::header("List of profiles");
		if ( !empty($data->body->data))
		{
			foreach ($data->body->data as $profile)
			{
				$id = sprintf('%4u', $profile->id);
				RemoteUtilsRender::info($id . "|{$profile->name}", true);
			}
		}
	}

	/**
	 * Lists the backup records on the server
	 */
	private function listbackups()
	{
		$options = RemoteUtilsCli::getInstance();
		$api     = RemoteApi::getInstance();

		$this->test('com_akeeba', false);

		$from  = (int)($options->get('from', 0));
		$limit = (int)($options->get('limit', 50));

		$data = $api->doQuery('listBackups', array('from' => $from, 'limit' => $limit));

		if ($data->body->status != 200)
		{
			throw new RemoteExceptionCantlistrecords("Could not list backup records");
		}

		RemoteUtilsRender::header("List of backup records");
		//var_dump($data->body->data);return;
		if ( !empty($data->body->data))
		{
			foreach ($data->body->data as $record)
			{
				$id     = sprintf('%6u', $record->id);
				$status = ($record->status == 'complete') && !($record->filesexist) ? 'obsolete' : $record->status;
				$status = str_pad($status, 8);
				RemoteUtilsRender::info($id . "|{$record->backupstart}|$status|{$record->description}", true);
			}
		}
	}

	/**
	 * Returns detailed information about a specific backup record
	 */
	private function backupinfo()
	{
		$options = RemoteUtilsCli::getInstance();
		$api     = RemoteApi::getInstance();

		$id = (int)($options->get('id', 0));

		if ($id <= 0)
		{
			throw new RemoteAppExceptionBackupid();
		}

		$this->test('com_akeeba', false);

		$data = $api->doQuery('getBackupInfo', array('backup_id' => $id));

		if ($data->body->status != 200)
		{
			throw new RemoteExceptionRecordinfo("Could not get information of backup record $id");
		}

		RemoteUtilsRender::header("Information about backup record $id");
		$data = get_object_vars($data->body->data);
		foreach ($data as $k => $v)
		{
			if (is_array($v))
			{
				continue;
			}
			if ($k == 'status')
			{
				$v = ($v == 'complete') && !($data['filesexist']) ? 'obsolete' : $v;
			}
			$k = str_pad($k, 20);
			RemoteUtilsRender::info("$k : $v", true);
		}
	}

	/**
	 * Updates Akeeba Backup on your site
	 */
	private function update()
	{
		$options = RemoteUtilsCli::getInstance();
		$api     = RemoteApi::getInstance();

		$force        = $options->get('force', 0);
		$minStability = $options->get('minimum-stability', 'alpha');

		$this->test('com_akeeba', false);

		// Get version information
		$data = $api->doQuery('updateGetInformation', array('force' => $force));
		if ($data->body->status != 200)
		{
			throw new RemoteExceptionUpdateinfo("Could not get update information");
		}

		// Is it supported?
		if ( !$data->body->data->supported)
		{
			throw new RemoteExceptionLiveupdatesupport("Live Update is not supported on this site.");
		}

		// Is it stuck?
		if ($data->body->data->stuck)
		{
			if ($force)
			{
				throw new RemoteExceptionLiveupdatestuck("Live Update reports that it is stuck.");
			}
			else
			{
				throw new RemoteExceptionLiveupdatestuck("Live Update reports that it is stuck. Try using --force=1.");
			}
		}

		// Do we have updates?
		if ( !$data->body->data->hasUpdates)
		{
			throw new RemoteExceptionNoupdates("No updates are available at this time");
		}

		RemoteUtilsRender::header('Update found', true);
		RemoteUtilsRender::info("Version   : {$data->body->data->version}");
		RemoteUtilsRender::info("Date      : {$data->body->data->date}");
		RemoteUtilsRender::info("Stability : {$data->body->data->stability}");

		// Is it stable enough?
		$stabilities = array('alpha' => 0, 'beta' => 30, 'rc' => 60, 'stable' => 100);
		$min         = array_key_exists($minStability, $stabilities) ? $stabilities[$minStability] : 0;
		$cur         = array_key_exists($data->body->data->stability, $stabilities) ? $stabilities[$data->body->data->stability] : 0;
		if ($cur < $min)
		{
			throw new RemoteExceptionUpdatestability("The available version does not fulfil your minimum stability preferences");
		}

		// Download
		RemoteUtilsRender::header('Downloading update');
		$data = $api->doQuery('updateDownload', array());
		if ($data->body->status != 200)
		{
			throw new RemoteExceptionUpdatedownload("Download error : " . $data->body->data);
		}
		RemoteUtilsRender::info('Update downloaded to your server', true);

		// Extract
		RemoteUtilsRender::header('Extracting update');
		$data = $api->doQuery('updateExtract', array());
		if ($data->body->status != 200)
		{
			throw new RemoteExceptionUpdateextraction("Extraction error : " . $data->body->data);
		}
		RemoteUtilsRender::info('Update extracted to your server', true);

		// Installation
		RemoteUtilsRender::header('Installing update');
		$data = $api->doQuery('updateInstall', array());
		if ($data->body->status != 200)
		{
			throw new RemoteExceptionUpdateinstallation("Installation error : " . $data->body->data);
		}
		RemoteUtilsRender::info('Update installed to your server', true);

		// Clean-up
		RemoteUtilsRender::header('Cleaning up');
		$data = $api->doQuery('updateCleanup', array());
		if ($data->body->status != 200)
		{
			throw new RemoteExceptionUpdatecleanup("Cleanup error : " . $data->body->data);
		}
		RemoteUtilsRender::info('Cleanup complete', true);
	}

	/* // // // // // // // // // // // // // // // // // // // // // // // // // //
	 * **************** WARNING: CONSTRUCTION ZONE. DO NOT ENTER. ******************
	 * // // // // // // // // // // // // // // // // // // // // // // // // // //
	 */
	/*
		private function jversion()
		{
			$options = RemoteUtilsCli::getInstance();
			$api = RemoteApi::getInstance();

			$this->test('com_admintools');

			// Get version information
			$data = $api->doQuery('getJoomlaVersion', array(), 'com_admintools');
			if($data->body->status != 200) {
				throw new RemoteException("Could not get update information");
			}

			RemoteUtilsRender::header("Joomla! Version information");

			RemoteUtilsRender::info("Installed version : {$data->body->data->current}");
			RemoteUtilsRender::info("Newest version    : {$data->body->data->version}");
		}

		private function jupdate()
		{
			$options = RemoteUtilsCli::getInstance();
			$api = RemoteApi::getInstance();

			$this->test('com_admintools');

			$reinstall = $options->get('reinstall',0);

			// Get version information
			$data = $api->doQuery('getJoomlaVersion', array(), 'com_admintools');
			if($data->body->status != 200) {
				throw new RemoteException("Could not get update information");
			}

			if(!$reinstall && !($data->body->data->status)) {
				RemoteUtilsRender::header("There are no Joomla! updates available");
			} elseif(!$reinstall) {
				RemoteUtilsRender::header("Joomla! Update");
				RemoteUtilsRender::info("Updating to Joomla! {$data->body->data->version}");
			} else {
				RemoteUtilsRender::header("Joomla! Re-installation");
				RemoteUtilsRender::info("Reinstalling Joomla! {$data->body->data->version}");
			}

			// @todo Generate and get the password to the installation script

			// @todo Step through the installation
		}
	*/
}