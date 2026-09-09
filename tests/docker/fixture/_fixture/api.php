<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

/**
 * The Akeeba Backup JSON API, as far as Remote CLI can tell.
 *
 * This is the far end of the integration suite: a real web server answering real HTTP, speaking all three versions of
 * the JSON API with the response shapes, authentication rules and error statuses the real thing uses. It is not Akeeba
 * Backup — it takes no backups and writes no archives worth the name — and it does not need to be. What Remote CLI
 * does is talk to a server, and everything the suite asserts about it is observable from this side of the wire.
 *
 * Using a real Joomla site with Akeeba Backup Professional instead is not an option a test suite can rely on: the
 * Professional package is licence-gated, so nobody could run the suite from a fresh checkout, and a real site cannot
 * be asked on demand to return a restricted token, bury its answer in PHP warnings, or forget how to speak v3.
 *
 * @since 3.2.0
 */

const FIXTURE_STATE_FILE = '/var/www/state/state.json';

/**
 * The API level this fixture reports. It has to be at or above ARCCLI_MINAPI or every connection is refused.
 */
const FIXTURE_API_LEVEL = 600;

/**
 * The methods the restricted API token may not call.
 *
 * Since Akeeba Backup 10.4.0 the v3 API enforces the token's Joomla user account privileges per method, so a token can
 * authenticate perfectly well and still be refused. Only the v3 API can produce this; the Secret Word is a blanket
 * grant over everything.
 */
const FIXTURE_RESTRICTED_METHODS = ['delete', 'deleteFiles', 'download', 'downloadDirect'];

function fixture_state(): array
{
	if (!is_file(FIXTURE_STATE_FILE))
	{
		fixture_reset();
	}

	return json_decode((string) file_get_contents(FIXTURE_STATE_FILE), true) ?: [];
}

function fixture_save(array $state): void
{
	file_put_contents(FIXTURE_STATE_FILE, json_encode($state, JSON_PRETTY_PRINT), LOCK_EX);
}

/**
 * Returns the fixture to the state every test starts from.
 */
function fixture_reset(): void
{
	@mkdir(dirname(FIXTURE_STATE_FILE), 0o777, true);

	fixture_save(
		[
			// Behaviours armed for the next request or two. See control.php.
			'armed'    => [],
			// Every request this container has been sent, so a test can ask what actually arrived.
			'requests' => [],
			// Which API versions this container is willing to answer on. Narrowed to test version negotiation.
			'versions' => [1, 2, 3],
			'profiles' => [
				['id' => 1, 'name' => 'Full site backup'],
				['id' => 2, 'name' => 'Nightly database only'],
			],
			'records'  => [
				fixture_record(1, 'A single part backup', '2026-01-01 03:00:00', ['single-2026-01-01.jpa']),
				fixture_record(
					2,
					'A three part backup',
					'2026-01-02 03:00:00',
					['multi-2026-01-02.j01', 'multi-2026-01-02.j02', 'multi-2026-01-02.jpa']
				),
			],
			'backups'  => [],
			'nextId'   => 3,
		]
	);
}

/**
 * Builds one backup record, in the shape Akeeba Backup's API actually returns.
 *
 * Every field here is read by one of Remote CLI's commands. A record missing `status`, `filesexist` or `backupstart`
 * makes listbackups emit PHP warnings and print nonsense, which is a fixture bug that looks exactly like a product
 * bug — so the shape lives in one place rather than being spelled out per record.
 *
 * @param   int       $id           The record ID
 * @param   string    $description  Its description
 * @param   string    $start        When the backup started
 * @param   string[]  $partNames    The archive part filenames, in order. Empty means the files are gone.
 */
function fixture_record(int $id, string $description, string $start, array $partNames, string $comment = ''): array
{
	$files = [];

	foreach (array_values($partNames) as $index => $name)
	{
		$files[] = ['part' => $index + 1, 'name' => $name];
	}

	$size = 0;

	foreach ($files as $file)
	{
		$size += strlen(fixture_archive_bytes($id, (int) $file['part']));
	}

	return [
		'id'              => $id,
		'description'     => $description,
		'comment'         => $comment,
		'backupstart'     => $start,
		'backupend'       => $start,
		'status'          => $files === [] ? 'complete' : 'complete',
		'origin'          => 'json',
		'type'            => 'full',
		'profile_id'      => 1,
		'meta'            => $files === [] ? 'obsolete' : 'ok',
		'filesexist'      => $files !== [],
		'remote_filename' => '',
		'multipart'       => count($files),
		'size'            => $size,
		'absolute_path'   => '/var/www/backups/' . ($files[0]['name'] ?? ''),
		'files'           => $files,
	];
}

/**
 * The bytes of one part of one backup archive.
 *
 * Deterministic, so a test can work out what the whole part should look like without downloading it twice, and long
 * enough that a chunked download takes more than one chunk.
 */
function fixture_archive_bytes(int $recordId, int $part): string
{
	return str_repeat(sprintf('AKEEBA-%d-%02d;', $recordId, $part), 4096);
}

function fixture_secret(): string
{
	return (string) getenv('FIXTURE_SECRET');
}

function fixture_token(): string
{
	return (string) getenv('FIXTURE_TOKEN');
}

function fixture_restricted_token(): string
{
	return (string) getenv('FIXTURE_RESTRICTED_TOKEN');
}

/**
 * Records the request being served, so a test can ask what actually reached this container.
 *
 * This is what makes "the credential was never disclosed" an assertion about the world rather than about a log line.
 *
 * Called by each endpoint as soon as it knows which version and method it is looking at, and deliberately *before*
 * authentication: a request which was refused is the most interesting one to inspect, because it is the one carrying
 * the credential a test wants to prove arrived intact.
 */
function fixture_record_request(int $apiVersion, string $apiMethod): void
{
	$state = fixture_state();

	$state['requests'][] = [
		'time'       => microtime(true),
		'apiVersion' => $apiVersion,
		'method'     => $apiMethod,
		'verb'       => $_SERVER['REQUEST_METHOD'] ?? '',
		'uri'        => $_SERVER['REQUEST_URI'] ?? '',
		'host'       => $_SERVER['HTTP_HOST'] ?? '',
		'headers'    => [
			'X-Joomla-Token' => $_SERVER['HTTP_X_JOOMLA_TOKEN'] ?? null,
			'X-Akeeba-Auth'  => $_SERVER['HTTP_X_AKEEBA_AUTH'] ?? null,
			'User-Agent'     => $_SERVER['HTTP_USER_AGENT'] ?? null,
			'Accept'         => $_SERVER['HTTP_ACCEPT'] ?? null,
		],
	];

	fixture_save($state);
}

/**
 * Takes the next armed behaviour of a kind, if there is one, decrementing what is left of it.
 */
function fixture_take_armed(string $kind): ?array
{
	$state = fixture_state();
	$armed = $state['armed'] ?? [];

	foreach ($armed as $index => $entry)
	{
		if (($entry['kind'] ?? '') !== $kind)
		{
			continue;
		}

		$entry['count']--;

		if ($entry['count'] <= 0)
		{
			unset($armed[$index]);
		}
		else
		{
			$armed[$index] = $entry;
		}

		$state['armed'] = array_values($armed);

		fixture_save($state);

		return $entry;
	}

	return null;
}

/**
 * Is this container willing to speak this version of the API at all?
 *
 * Narrowing it is how the suite tests version negotiation: a container which only answers on v2 is what a site running
 * Akeeba Backup 9.0 looks like from the outside.
 */
function fixture_speaks(int $apiVersion): bool
{
	return in_array($apiVersion, fixture_state()['versions'] ?? [1, 2, 3], true);
}

/**
 * Wraps a response the way whichever junk behaviour is armed says to.
 *
 * Real sites do this to themselves, with a too-verbose error_reporting or a caching plugin appending a comment, and
 * the client has to dig its JSON back out of the mess.
 */
function fixture_decorate(string $json): string
{
	$armed = fixture_take_armed('junk');

	if ($armed === null)
	{
		return $json;
	}

	$notice  = '<br />' . "\n" . '<b>Notice</b>: Undefined index: foo in '
		. '<b>/var/www/html/wp-content/plugins/something/awful.php</b> on line <b>17</b><br />' . "\n";
	$comment = "\n" . '<!-- Page generated by a caching plugin in 0.031 seconds -->';

	return match ($armed['where'] ?? 'before')
	{
		'after'  => $json . $comment,
		'both'   => $notice . $json . $comment,
		'hashes' => '###' . $json . '###',
		default  => $notice . $json,
	};
}

function fixture_find_record(array $state, int $id): ?array
{
	foreach ($state['records'] as $record)
	{
		if ((int) $record['id'] === $id)
		{
			return $record;
		}
	}

	return null;
}

/**
 * Runs an API method.
 *
 * @return  array  [int $status, mixed $data]
 */
function fixture_dispatch(string $apiMethod, array $data): array
{
	$state = fixture_state();

	switch ($apiMethod)
	{
		case 'getVersion':
			return [
				200,
				[
					'api'     => FIXTURE_API_LEVEL,
					'version' => '10.4.1',
					'date'    => '2026-01-01',
					'edition' => 'pro',
					'secret'  => '',
				],
			];

		case 'getProfiles':
			return [200, $state['profiles']];

		case 'listBackups':
			$from  = max(0, (int) ($data['from'] ?? 0));
			$limit = min(max(1, (int) ($data['limit'] ?? 200)), 200);

			return [200, array_slice(array_reverse($state['records']), $from, $limit)];

		case 'getBackupInfo':
			$record = fixture_find_record($state, (int) ($data['backup_id'] ?? 0));

			if ($record === null)
			{
				return [404, 'No such backup record'];
			}

			return [
				200,
				array_merge(
					$record,
					[
						'filenames' => array_map(
							fn(array $file) => [
								'part' => $file['part'],
								'name' => $file['name'],
								'size' => strlen(fixture_archive_bytes((int) $record['id'], (int) $file['part'])),
							],
							$record['files']
						),
					]
				),
			];

		case 'delete':
		case 'deleteFiles':
			$record = fixture_find_record($state, (int) ($data['backup_id'] ?? 0));

			if ($record === null)
			{
				return [404, 'No such backup record'];
			}

			if ($apiMethod === 'delete')
			{
				$state['records'] = array_values(
					array_filter($state['records'], fn(array $x) => (int) $x['id'] !== (int) $record['id'])
				);
			}
			else
			{
				$state['records'] = array_map(
					function (array $candidate) use ($record) {
						if ((int) $candidate['id'] !== (int) $record['id'])
						{
							return $candidate;
						}

						$candidate['files']      = [];
						$candidate['multipart']  = 0;
						$candidate['meta']       = 'obsolete';
						$candidate['filesexist'] = false;
						$candidate['size']       = 0;

						return $candidate;
					},
					$state['records']
				);
			}

			fixture_save($state);

			return [200, 'true'];

		case 'startBackup':
			$backupId = 'bkp' . bin2hex(random_bytes(4));
			$recordId = (int) $state['nextId']++;

			$state['backups'][$backupId] = ['recordId' => $recordId, 'step' => 0];

			$state['records'][] = fixture_record(
				$recordId,
				(string) ($data['description'] ?? ''),
				gmdate('Y-m-d H:i:s'),
				[sprintf('remote-%d.jpa', $recordId)],
				(string) ($data['comment'] ?? '')
			);

			fixture_save($state);

			return [
				200,
				[
					'HasRun'   => true,
					'Domain'   => 'init',
					'Step'     => 'Initialising',
					'Substep'  => '',
					'Progress' => 0,
					'Warnings' => [],
					'Error'    => '',
					'backupid' => $backupId,
					'BackupID' => $recordId,
					'Archive'  => sprintf('remote-%d.jpa', $recordId),
				],
			];

		case 'stepBackup':
			$backupId = (string) ($data['backupid'] ?? '');

			if (!isset($state['backups'][$backupId]))
			{
				return [500, 'No such backup in progress'];
			}

			$state['backups'][$backupId]['step']++;
			$step     = $state['backups'][$backupId]['step'];
			$recordId = $state['backups'][$backupId]['recordId'];
			$finished = $step >= 2;

			// A warning on the way through, so the suite can assert Remote CLI relays it.
			$warnings = $step === 1 ? ['The fixture is only pretending to back anything up'] : [];

			fixture_save($state);

			return [
				200,
				[
					'HasRun'   => !$finished,
					'Domain'   => $finished ? 'finale' : 'pack',
					'Step'     => $finished ? 'Finished' : 'Packing files',
					'Substep'  => '',
					'Progress' => $finished ? 100 : 50,
					'Warnings' => $warnings,
					'Error'    => '',
					'backupid' => $backupId,
					'BackupID' => $recordId,
					'Archive'  => sprintf('remote-%d.jpa', $recordId),
				],
			];

		/**
		 * The chunked download mode. Rather than streaming the archive, it asks for one Base64-encoded segment at a
		 * time until the server answers 404, which is how it copes with servers that cannot stream a large response.
		 */
		case 'download':
			$record = fixture_find_record($state, (int) ($data['backup_id'] ?? 0));
			$part   = (int) ($data['part'] ?? 1);

			if ($record === null || $part < 1 || $part > (int) $record['multipart'])
			{
				return [404, 'No such part'];
			}

			$bytes = fixture_archive_bytes((int) $record['id'], $part);
			// The client counts segments from 1 and sends the chunk size in MiB.
			$chunkSize = max(1, (int) ($data['chunk_size'] ?? 1)) * 1048576;
			$segment   = max(1, (int) ($data['segment'] ?? 1));
			$offset    = ($segment - 1) * $chunkSize;

			// Running off the end is how the client is told there is nothing more of this part to fetch.
			if ($offset >= strlen($bytes))
			{
				return [404, 'No more data'];
			}

			return [200, base64_encode(substr($bytes, $offset, $chunkSize))];

		case 'exportConfiguration':
			$profileId = (int) ($data['profile'] ?? 0);

			foreach ($state['profiles'] as $profile)
			{
				if ((int) $profile['id'] !== $profileId)
				{
					continue;
				}

				return [
					200,
					[
						'description'   => $profile['name'],
						'configuration' => json_encode(['akeeba' => ['basic' => ['output_directory' => '[DEFAULT]']]]),
						'filters'       => json_encode([]),
					],
				];
			}

			return [404, 'No such profile'];

		case 'importConfiguration':
			$profileId = (int) count($state['profiles']) + 1;

			$state['profiles'][] = [
				'id'   => $profileId,
				'name' => (string) (($data['data']['description'] ?? null) ?: 'Imported profile'),
			];

			fixture_save($state);

			/**
			 * `true`, which is what a real Akeeba Backup site answers here — verified against Akeeba Backup 10.4.1.
			 * The client cannot cope with it; see ProfileTest::testExportedProfileCanBeImported. Answering with an
			 * array instead would make the suite green by making the fixture lie about the server.
			 */
			return [200, true];
	}

	// Exactly what a real server says about a method it has never heard of.
	return [405, sprintf('Unknown method %s', $apiMethod)];
}

/**
 * Answers a request, having already worked out which API version and method it is.
 *
 * @param   int     $apiVersion  1, 2 or 3
 * @param   string  $apiMethod   The method being called
 * @param   array   $data        Its payload
 * @param   bool    $restricted  Whether the caller authenticated with the restricted token
 */
function fixture_respond(int $apiVersion, string $apiMethod, array $data, bool $restricted = false): never
{
	if ($restricted && in_array($apiMethod, FIXTURE_RESTRICTED_METHODS, true))
	{
		fixture_emit($apiVersion, 403, sprintf('You are not allowed to call %s', $apiMethod));
	}

	// downloadDirect streams an archive part rather than answering with JSON.
	if ($apiMethod === 'downloadDirect')
	{
		$state  = fixture_state();
		$record = fixture_find_record($state, (int) ($data['backup_id'] ?? 0));
		$part   = (int) ($data['part_id'] ?? 1);

		if ($record === null || $part < 1 || $part > (int) $record['multipart'])
		{
			http_response_code(404);

			exit;
		}

		$bytes = fixture_archive_bytes((int) $record['id'], $part);
		$range = $_SERVER['HTTP_RANGE'] ?? '';

		if (preg_match('#bytes=(\d+)[=-](\d+)#', $range, $matches))
		{
			$from  = (int) $matches[1];
			$to    = min((int) $matches[2], strlen($bytes) - 1);
			$bytes = substr($bytes, $from, $to - $from + 1);

			http_response_code(206);
		}

		header('Content-Type: application/octet-stream');
		header('Content-Length: ' . strlen($bytes));

		echo $bytes;

		exit;
	}

	[$status, $data] = fixture_dispatch($apiMethod, $data);

	fixture_emit($apiVersion, $status, $data);
}

/**
 * Writes the response in the shape the requested API version uses.
 */
function fixture_emit(int $apiVersion, int $status, mixed $data): never
{
	$body = ['status' => $status, 'data' => $data];

	if ($apiVersion === 1)
	{
		/**
		 * The v1 API encapsulates its answer, wrapped in ### markers: an envelope whose `body` is an object carrying
		 * the status and the data, and whose `data` is JSON-encoded a second time inside that.
		 *
		 * Note the asymmetry with the *request*, where `body` really is a JSON string. Encoding the response the same
		 * way makes the client report invalid JSON, because it looks for a `data` property on what is then a string.
		 */
		$payload = json_encode(
			['encapsulation' => 1, 'body' => ['status' => $status, 'data' => json_encode($data)]]
		);

		header('Content-Type: text/plain');

		echo fixture_decorate('###' . $payload . '###');

		exit;
	}

	header('Content-Type: application/json');

	echo fixture_decorate((string) json_encode($body));

	exit;
}
