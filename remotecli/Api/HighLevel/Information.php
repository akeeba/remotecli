<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */


namespace Akeeba\RemoteCLI\Api\HighLevel;


use Akeeba\RemoteCLI\Api\Connector;

class Information
{
	public function __construct(private Connector $connector){}

	public function __invoke(): object
	{
		return $this->connector->doQuery('getVersion');
	}
}
