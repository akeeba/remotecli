<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

/**
 * The site's frontend: where the JSON API v1 and v2 live.
 *
 * Both are addressed as a view of a Joomla component, which is why the version is decided by the `view` parameter and
 * the credential travels in the query string. That is exactly what the v3 API was built to stop doing.
 */

require_once __DIR__ . '/_fixture/api.php';

$view = strtolower((string) ($_REQUEST['view'] ?? ''));

// The JSON API v1: view=json, with the method and payload encapsulated in the `json` parameter.
if ($view === 'json')
{
	if (!fixture_speaks(1))
	{
		http_response_code(404);

		exit;
	}

	$envelope = json_decode((string) ($_REQUEST['json'] ?? ''), true);
	$body     = json_decode((string) ($envelope['body'] ?? ''), true);

	if (!is_array($body))
	{
		fixture_emit(1, 500, 'Could not decode the encapsulated request');
	}

	fixture_record_request(1, (string) ($body['method'] ?? ''));

	/**
	 * The v1 API authenticates with a challenge: a random salt, and the MD5 of that salt concatenated with the Secret
	 * Word. Checking it properly is the only way the suite can prove Remote CLI still speaks v1 correctly.
	 */
	[$salt, $digest] = array_pad(explode(':', (string) ($body['challenge'] ?? ''), 2), 2, '');

	if ($digest === '' || !hash_equals(md5($salt . fixture_secret()), $digest))
	{
		fixture_emit(1, 503, 'Authentication failed');
	}

	fixture_respond(1, (string) ($body['method'] ?? ''), (array) ($body['data'] ?? []));
}

// The JSON API v2: view=Api, method=<name>, credential in _akeebaAuth.
if ($view === 'api')
{
	if (!fixture_speaks(2))
	{
		http_response_code(404);

		exit;
	}

	fixture_record_request(2, (string) ($_REQUEST['method'] ?? ''));

	if (!hash_equals(fixture_secret(), (string) ($_REQUEST['_akeebaAuth'] ?? '')))
	{
		fixture_emit(2, 503, 'Authentication failed');
	}

	$data = array_diff_key(
		$_REQUEST,
		array_flip(['option', 'view', 'format', 'tmpl', 'method', '_akeebaAuth', 'action'])
	);

	fixture_respond(2, (string) ($_REQUEST['method'] ?? ''), $data);
}

/**
 * Anything else is the site's home page. A real Joomla site answers 200 with HTML here, and saying so is what lets the
 * suite prove Remote CLI does not mistake a working web server for a working API.
 */
header('Content-Type: text/html');

echo "<!doctype html><title>Fixture site</title><p>This is the fixture site's home page.</p>";
