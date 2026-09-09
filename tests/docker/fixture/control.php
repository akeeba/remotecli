<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

/**
 * The fixture's back door, used by the suite and by nothing else.
 *
 * It is what lets a test start from a known state, ask what actually reached the server, and arm the behaviours a real
 * site cannot be asked for on demand — answering on only some API versions, or burying its answer in PHP warnings.
 *
 * It is not part of the API surface under test and nothing in Remote CLI knows it exists.
 */

require_once __DIR__ . '/_fixture/api.php';

header('Content-Type: application/json');

$action = (string) ($_REQUEST['action'] ?? '');

switch ($action)
{
	case 'reset':
		fixture_reset();

		echo json_encode(['ok' => true]);

		break;

	case 'state':
		echo json_encode(fixture_state());

		break;

	case 'requests':
		echo json_encode(fixture_state()['requests'] ?? []);

		break;

	// Narrow the API versions this container will answer on, so version negotiation can be tested.
	case 'versions':
		$state             = fixture_state();
		$state['versions'] = array_values(
			array_filter(
				array_map('intval', explode(',', (string) ($_REQUEST['versions'] ?? '1,2,3'))),
				fn(int $version) => in_array($version, [1, 2, 3], true)
			)
		);

		fixture_save($state);

		echo json_encode(['ok' => true, 'versions' => $state['versions']]);

		break;

	// Arm a behaviour for the next $count requests.
	case 'arm':
		$state            = fixture_state();
		$state['armed'][] = [
			'kind'  => (string) ($_REQUEST['kind'] ?? ''),
			'where' => (string) ($_REQUEST['where'] ?? 'before'),
			'count' => max(1, (int) ($_REQUEST['count'] ?? 1)),
		];

		fixture_save($state);

		echo json_encode(['ok' => true]);

		break;

	default:
		http_response_code(400);

		echo json_encode(['ok' => false, 'error' => 'Unknown action']);
}
