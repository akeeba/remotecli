<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

use Akeeba\RemoteCLI\Application\Input\Cli;
use Akeeba\RemoteCLI\Application\Output\Output;

class Help extends AbstractCommand
{
	/**
	 * @inheritDoc
	 */
	public function execute(Cli $input, Output $output): void
	{
		if ($input->getBool('quiet') || $input->getBool('m') || $input->getBool('machine-readable'))
		{
			$output->error('Help is only available in regular (not machine-readable) output format.');

			return;
		}

		global $argv;
		$self = basename($argv[0]);
		$version = ARCCLI_VERSION;

		echo <<< PLAINTEXT

USAGE
--------------------------------------------------------------------------------
	$self <command> [options]

OUTPUT OPTIONS
--------------------------------------------------------------------------------
These options apply to all commands and change the way output is generated.

--machine-readable, or -m
    Format the output to be parsed by other programmes.

--debug
    Display debug information. Also creates the log file remotecli_log.txt in the current working directory.

--quiet
    Display minimal amount of messages in the standard output.

COMMON OPTIONS
--------------------------------------------------------------------------------
These options apply to all commands talking to a site.

--host <URL>, or -h <URL>
    Which site to connect to.
    e.g. --host https://www.example.com

--secret <SECRET_KEY>, or -s <SECRET_KEY>
    The Secret Key for the Akeeba Backup / Akeeba Solo JSON API.
    e.g. --secret q6TFprWKACdsKxQDBtthZkQU

--certificate <PATH>
    Add a PEM certificate as a valid HTTPS Certification Authority. Use with sites using self-signed certificates.
    e.g. --certificate /home/myuser/ssl/public_key.pem

ADVANCED OPTIONS
--------------------------------------------------------------------------------
You should not need to use these options unless our support instructs you to.

--verb <HTTP_VERB>
    Which HTTP verb to use. One of POST, or GET. Default: auto-detect, prefers GET.

--component <OPTION>
     Which component to use. One of com_akeeba (Akeeba Backup for Joomla 3) or com_akeebabackup (Akeeba Backup for
     Joomla 4). Default: auto-detect. Do not use with Akeeba Backup for WordPress or Akeeba Solo.

--view <VIEW_NAME>
    Which view to use. One of json (API v1) or api (API v2). Default: auto-detect.

--format <FORMAT_NAME>
    The format keyword to send to Joomla sites. One of html, json, raw. Default: auto-detect.

--ua <USER_AGENT>
    Set the User Agent string. Default: "AkeebaRemoteCLI/$version".
    e.g. --ua "Foo FooBar/1.2.3"

DOWNLOAD OPTIONS
--------------------------------------------------------------------------------
Applicable to the download and backup commands (the latter only if used with the --download flag).

--dlmode
    Optional. Download mode (default: http). One of:
      http
        Download entire backup archive files over HTTP. Fast, but may result in corrupt archives.

      chunk
        Download backup archives over HTTP, 10MiB at a time. Slower, more reliable, unless you have server issues.

      curl
        Use cURL to download your backup archives over FTP, FTPS (FTP over Explicit SSL/TLS), or SFTP. Most reliable,
        very fast, but the hardest to set up.

--dlpath
    Optional. Path on your computer to store the downloaded file(s) into. Default: current working directory. 

--dlurl
    Only required for --mode curl. Defines the cURL URL for downloading backup archives. Read the documentation!
    e.g. --dlurl "ftp://myuser:mypassword@ftp.example.com:21/public_html/administrator/components/com_akeeba/backup"

--filename
    Optional. Rename the downloaded file to this filename. Only applicable when downloading a single part.

--delete
    Optional. When present, the backup archive files will be removed from the server when the download completes.

--part
    Optional. Download only a single archive part from a multipart backup archive. Omit to download all parts.

--chunk_size
    Optional. How many Megabytes (MiB) to download per chunk when using the chunk method. Default: 10. Minimum: 1.


COMMANDS
--------------------------------------------------------------------------------

help
    This help text

license
    Display the software license (GNU GPL v3)

php
    Display information about the PHP execution environment.

test
    Tests the connection to your site. Use with --debug for detailed information. 

backup
    TODO

backupinfo
    TODO

listbackups
    TODO

delete
    TODO

deletefiles
    TODO

download
    TODO

profiles
    TODO

profileexport
    TODO

profileimport
    TODO

update
    TODO


PLAINTEXT;

	}
}