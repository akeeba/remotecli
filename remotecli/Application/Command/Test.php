<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Application\Command;

class Test extends AbstractCommand
{
	public function execute(): void
	{
		$this->assertConfigured();

		$api         = $this->getApiObject();
		$apiResult   = $api->information();
		$versionInfo = $apiResult->body->data;
		$version     = $versionInfo->component . ' (API level ' . $apiResult->body->data->api . ')';
		$edition     = (($versionInfo->edition ?? '') === 'pro') ? 'Professional ' : 'Core ';
		$edition     = ($versionInfo->edition ?? '') === '' ? '' : $edition;

		$this->output->info("Successful connection to site");
		$this->output->info("Akeeba Backup / Solo $edition$version");
		$this->output->info($this->describeConnection());
		$this->output->info('');
	}

	/**
	 * Describes the connection settings autodetect() settled on.
	 *
	 * This is the one thing the test command can tell you that no other command will: which of the three JSON API
	 * versions your site actually answered on, and which of your two credentials it accepted. Both matter now that a
	 * site may speak more than one version, and that a Secret Word you provided may have been used as a Joomla API
	 * token instead — the API v3 accepts a token in place of the Secret Word, and we try it that way first.
	 *
	 * @return  string
	 * @since   3.2.0
	 */
	private function describeConnection(): string
	{
		$options = $this->getNegotiatedOptions();

		if ($options === null)
		{
			return '';
		}

		// DO NOT REMOVE the local variable. empty() does NOT work on magic properties!
		$token = trim((string) ($options->token ?? ''));

		return sprintf(
			'Using the JSON API v%d, authenticating with %s.',
			$options->apiVersion,
			$token === '' ? 'the Secret Word' : 'a Joomla API Token'
		);
	}
}
