<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\OLD\RemoteCLI\Download;

use Akeeba\RemoteCLI\Api\Exception\CommunicationError;

class Download
{
	/**
	 * Parameters passed from the GUI when importing from URL
	 *
	 * @var  array
	 */
	private $params = array();

	/**
	 * The download adapter which will be used by this class
	 *
	 * @var  DownloadInterface
	 */
	private $adapter = null;

    /**
     * Additional params that will be passed to the adapter while performing the download
     *
     * @var  array
     */
    private $adapterOptions = array();

	public function __construct()
	{
		// Find the best fitting adapter
		$allAdapters = self::getFiles(__DIR__ . '/Adapter', array(), array('AbstractAdapter.php'));
		$priority    = 0;

		foreach ($allAdapters as $adapterInfo)
		{
			/** @var \Akeeba\OLD\RemoteCLI\Download\Adapter\AbstractAdapter $adapter */
			$adapter = new $adapterInfo['classname'];

			if (!$adapter->isSupported())
			{
				continue;
			}

			if ($adapter->priority > $priority)
			{
				$this->adapter = $adapter;
				$priority      = $adapter->priority;
			}
		}
	}

    /**
     * Returns the name of the current adapter
     *
     * @return  string
     */
    public function getAdapterName(): string
    {
	    if (!is_object($this->adapter))
	    {
		    return '';
	    }

	    $class = get_class($this->adapter);

	    return strtolower(str_ireplace('Akeeba\\RemoteCLI\\Download\\Adapter\\', '', $class));
    }

    /**
     * Sets the additional options for the adapter
     *
     * @param   array  $options
     *
     * @codeCoverageIgnore
     */
    public function setAdapterOptions(array $options): void
    {
        $this->adapterOptions = $options;
    }

	/**
	 * Download data from a URL and return it
	 *
	 * @param   string  $url            The URL to download from.
	 * @param   bool    $useExceptions  Set to false to return false on failure instead of throwing an exception.
	 * @param   null    $fp             A file pointer to download to. If provided, the method returns null.
	 *
	 * @return  bool|string  The downloaded data. If $useExceptions is true it returns false on failure.
	 *
	 * @throws CommunicationError When there is an error communicating with the server
	 */
	public function getFromURL(string $url, bool $useExceptions = true, $fp = null)
	{
		try
		{
            return $this->adapter->downloadAndReturn($url, null, null, $this->adapterOptions, $fp);
		}
		catch (CommunicationError $e)
		{
			if ($useExceptions)
			{
				throw $e;
			}

			return false;
		}
	}

	/**
	 * POST data to a URL and return the response
	 *
	 * @param   string  $url            The URL to send the data to.
	 * @param   string  $data           The data to send to the server. If they need to be URL-encoded you have to do it
	 *                                  yourself.
	 * @param   string  $contentType    The type of the form data. The default is application/x-www-form-urlencoded.
	 * @param   bool    $useExceptions  Set to false to return false on failure instead of throwing an exception.
	 *
	 * @return  bool|string  The downloaded data. If $useExceptions is true it returns false on failure.
	 *
	 * @throws CommunicationError When there is an error communicating with the server
	 */
	public function postToURL(string $url, string $data = '', string $contentType = 'application/x-www-form-urlencoded', bool $useExceptions = true)
	{
		try
		{
			return $this->adapter->postAndReturn($url, $data, 'application/x-www-form-urlencoded', $this->adapterOptions);
		}
		catch (CommunicationError $e)
		{
			if ($useExceptions)
			{
				throw $e;
			}

			return false;
		}
	}

	/**
	 * This method will crawl a starting directory and get all the valid files
	 * that will be analyzed by __construct. Then it organizes them into an
	 * associative array.
	 *
	 * @param   string  $path           Folder where we should start looking
	 * @param   array   $ignoreFolders  Folder ignore list
	 * @param   array   $ignoreFiles    File ignore list
	 *
	 * @return  array   Associative array, where the `fullpath` key contains the path to the file,
	 *                  and the `classname` key contains the name of the class
	 */
	protected static function getFiles(string $path, array $ignoreFolders = array(), array $ignoreFiles = array()): array
	{
		$return = array();

		$files = self::scanDirectory($path, $ignoreFolders, $ignoreFiles);

		// Ok, I got the files, now I have to organize them
		foreach ($files as $file)
		{
			$clean = str_replace($path, '', $file);
			$clean = trim(str_replace('\\', '/', $clean), '/');

			$parts = explode('/', $clean);

			$return[] = array(
				'fullpath'  => $file,
				'classname' => '\\Akeeba\\RemoteCLI\\Download\\Adapter\\' . ucfirst(basename($parts[0], '.php')),
			);
		}

		return $return;
	}

	/**
	 * Recursive function that will scan every directory unless it's in the
	 * ignore list. Files that aren't in the ignore list are returned.
	 *
	 * @param   string  $path           Folder where we should start looking
	 * @param   array   $ignoreFolders  Folder ignore list
	 * @param   array   $ignoreFiles    File ignore list
	 *
	 * @return  array   List of all the files
	 */
	protected static function scanDirectory(string $path, array $ignoreFolders = array(), array $ignoreFiles = array()): array
	{
		$return = array();

		$handle = @opendir($path);

		if (!$handle)
		{
			return $return;
		}

		while (($file = readdir($handle)) !== false)
		{
			if ($file == '.' || $file == '..')
			{
				continue;
			}

			$fullpath = $path . '/' . $file;

			if ((is_dir($fullpath) && in_array($file, $ignoreFolders)) || (is_file($fullpath) && in_array($file, $ignoreFiles)))
			{
				continue;
			}

			if (is_dir($fullpath))
			{
				$return = array_merge(self::scanDirectory($fullpath, $ignoreFolders, $ignoreFiles), $return);
			}
			else
			{
				$return[] = $path . '/' . $file;
			}
		}

		return $return;
	}
}
