<?php
/**
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

/**
 * !!! IMPORTANT !!!
 *
 * This file is required for the PHAR archive to be executable. Do not remove.
 */
Phar::mapPhar('remotecli.phar');
require_once 'phar://remotecli.phar/remote.php';

__HALT_COMPILER();
