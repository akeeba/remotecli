<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Api\DataShape;

use Joomla\Data\DataObject;

/**
 * Options for running a backup
 *
 * @property int    $profile      Backup profile number
 * @property string $description  Backup description
 * @property string $comment      Backup comment
 *
 * @since 3.0.0
 */
class BackupOptions extends DataObject
{
	public function __construct($properties = [])
	{
		parent::__construct(array_merge(
			[
				'profile'     => 1,
				'description' => 'Remote backup',
				'comment'     => '',
			],
			$properties
		));
	}

}