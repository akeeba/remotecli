<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

/**
 * The Akeeba Solo endpoint.
 *
 * Solo is not Joomla, so it has no API application and no Joomla API Tokens: the v2 API and the Secret Word are all
 * there is. Remote CLI must not waste requests asking it for a v3 API which cannot exist.
 */

require_once __DIR__ . '/_fixture/api.php';

$_REQUEST['view'] = $_REQUEST['view'] ?? 'api';

require __DIR__ . '/index.php';
