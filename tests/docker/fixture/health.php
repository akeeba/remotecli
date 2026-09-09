<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

// What the container's healthcheck asks for. Deliberately touches no state.
header('Content-Type: text/plain');

echo 'OK';
