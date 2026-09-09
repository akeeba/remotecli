<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

/**
 * Joomla's API application: where the JSON API v3 lives.
 *
 * The route is /api/index.php/v3/akeebabackup/<method>, so the method is a path segment and the credential is a
 * request header. Neither ever appears in a URL, and therefore neither ever appears in an access log.
 */

require_once __DIR__ . '/../_fixture/api.php';

if (!fixture_speaks(3))
{
	http_response_code(404);

	exit;
}

$path = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');

if (!preg_match('#^v3/akeebabackup/(?<method>[A-Za-z0-9_]+)$#', $path, $matches))
{
	http_response_code(404);

	echo json_encode(['errors' => [['title' => 'Not Found']]]);

	exit;
}

// Recorded before authentication: a refused request is the one a test most wants to look at.
fixture_record_request(3, $matches['method']);

/**
 * Joomla's API application refuses a request which does not say what it accepts, and Akeeba Backup's webservices
 * plugin only papers over that for routes which matched. A client which forgets the header has to see the 406.
 */
if (!str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json'))
{
	http_response_code(406);

	echo json_encode(['errors' => [['title' => 'Not Acceptable']]]);

	exit;
}

$token  = (string) ($_SERVER['HTTP_X_JOOMLA_TOKEN'] ?? '');
$secret = (string) ($_SERVER['HTTP_X_AKEEBA_AUTH'] ?? '');

/**
 * The two credentials are mutually exclusive, and the Secret Word wins when both are present: a wrong Secret Word is a
 * hard failure rather than a fall-through to token authentication. Remote CLI relies on that, which is why it sends
 * exactly one of them.
 */
$restricted = false;

if ($secret !== '')
{
	if (!hash_equals(fixture_secret(), $secret))
	{
		fixture_emit(3, 503, 'Authentication failed');
	}
}
elseif ($token !== '')
{
	if (hash_equals(fixture_restricted_token(), $token))
	{
		$restricted = true;
	}
	elseif (!hash_equals(fixture_token(), $token))
	{
		fixture_emit(3, 503, 'Authentication failed');
	}
}
else
{
	fixture_emit(3, 503, 'No credential was presented');
}

$data = array_diff_key($_REQUEST, array_flip(['option', 'view', 'format', 'tmpl', 'method', '_akeebaAuth']));

fixture_respond(3, $matches['method'], $data, $restricted);
