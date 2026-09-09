<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

class Help extends AbstractCommand
{
	/**
	 * @inheritDoc
	 */
	public function execute(): void
	{
		if ($this->input->getBool('quiet') || $this->input->getBool('m') || $this->input->getBool('machine-readable'))
		{
			$this->output->error('Help is only available in regular (not machine-readable) output format.');

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
    Which site to connect to. Give us your site's root URL. The Endpoint URL of the JSON API v1 and v2, and the URL
    of Joomla's API application, are also accepted; we split the latter into a site and an --api-endpoint for you.
    e.g. --host https://www.example.com

--token <API_TOKEN>, or -t <API_TOKEN>
    Recommended. The Joomla API Token of the user account this tool will act as. Only understood by the JSON API v3,
    i.e. Akeeba Backup for Joomla 9.6.0 and later. The account's Akeeba Backup privileges are enforced per command.
    Find it in your site's backend, under User Menu, Edit Account, Joomla API Token.
    e.g. --token "c2hhMjU2OjQyOkVYQU1QTEUtVE9LRU4tTk9ULUEtUkVBTC1DUkVERU5USUFMUw=="

--secret <SECRET_KEY>, or -s <SECRET_KEY>
    Deprecated. The Secret Word for the Akeeba Backup / Akeeba Solo JSON API. It is an unscoped grant over the whole
    of the backup software. Still required for Akeeba Solo, Akeeba Backup for WordPress, and the JSON API v1 and v2.
    e.g. --secret q6TFprWKACdsKxQDBtthZkQU

You must provide at least one of --token and --secret. Providing both is allowed; the token is tried first, and the
Secret Word is only used if the token is refused.

--certificate <PATH>
    Add a PEM certificate as a valid HTTPS Certification Authority. Use with sites using self-signed certificates.
    e.g. --certificate /home/myuser/ssl/public_key.pem

ADVANCED OPTIONS
--------------------------------------------------------------------------------
You should not need to use these options unless our support instructs you to.

--verb <HTTP_VERB>
    Which HTTP verb to use. One of POST, or GET. Default: auto-detect, prefers GET.

--api-version <VERSION>
    Pin the JSON API version. One of 3 (Joomla only, Akeeba Backup 9.6.0 and later, the preferred version), 2, or 1.
    Default: auto-detect, newest first.

--api-endpoint <PATH>
    API v3 only. The path, relative to your site's root, of Joomla's API application.
    Default: auto-detect, trying api/index.php and then api.

--component <OPTION>
     API v1 and v2 only. Which component to use. One of com_akeeba (Akeeba Backup for Joomla 3) or com_akeebabackup
     (Akeeba Backup for Joomla 4 and later). Default: auto-detect. Do not use with Akeeba Backup for WordPress or
     Akeeba Solo.

--view <VIEW_NAME>
    Deprecated alias of --api-version. One of json (API v1) or api (API v2). Default: auto-detect.

--format <FORMAT_NAME>
    API v1 and v2 only. The format keyword to send to Joomla sites. One of html, json, raw. Default: auto-detect.

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
    Takes a backup of the site.
    
    --profile=<ID>
        Use the backup profile to take the backup. Default: 1

    --description=<DESCRIPTION>
        Set the backup record's description. Default: Remote Backup
    
    --comment=<COMMENT>
        Set the backup comment.

backupinfo
    Display information about a backup record
    
    --id=<ID>
        Required. Specify the backup record ID to list. 

listbackups
    Lists backup records in reverse chronologicla order.
    
    --from=<FROM>
        Skip this many items before starting listing records.
    
    --limit=<LIMIT>
        How many records to display, 1-200. Default: 200

delete
    Delete a backup record
    
    --id=<ID>
        Required. Specify the backup record ID to delete. 

deletefiles
    Delete the backup archives and log files of a backup record, but leaves the backup record in the database.

    --id=<ID>
        Required. Specify the backup record ID to delete files from. 

download
    Downloads the backup archives from a backup record. Requires using the DOWNLOAD OPTIONS documented above.
    
    --id=<ID>
        Required. Specify the backup record ID to download files from.     

profiles
    Lists all backup profiles.

profileexport
    Exports a backup profile to JSON format.
    
    --id=<ID>
        Required. Specify the backup profile ID to export     

    --
        Output to the standard output (STDOUT). Useful for piping the output ot other programmes. Use with -m --quiet
    
    --file=<FIILE>
        Set the file where the export will be written. Cannot be used with the -- flag above.  

profileimport
    Imports a backup profile from a profile exported to JSON.
    
    --data=<DATA>
        Use the given string as the profile JSON input.
    
    --file=<FILE>
        Use the given file's contents as the profile JSON input.
    
    --
        Use the standard input (STDIN) as the profile JSON input. Useful for piping from other programmes.

update
    Installs the latest update to the backup software. Only compatible with Akeeba Backup for WordPress and Akeeba Solo.
    This command will fail on Joomla sites, reporting the method does not exist on the server.


PLAINTEXT;

	}
}