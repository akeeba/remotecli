<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Unit\Stub;

use Akeeba\BackupJsonApi\Options;
use Akeeba\RemoteCLI\Application\Command\AbstractCommand;

/**
 * A command which does nothing, so that AbstractCommand's own behaviour can be tested.
 *
 * Every command in the application inherits its option handling — the short option mapping, the credential check, the
 * host normalisation — from AbstractCommand, and none of that needs a site to talk to. This subclass exposes the
 * protected parts of it and implements execute() as a no-op.
 *
 * @since 3.2.0
 */
class SpyCommand extends AbstractCommand
{
	public function execute(): void
	{
		// Deliberately does nothing. This command exists to be prepared, not to be run.
	}

	public function callAssertConfigured(): void
	{
		$this->assertConfigured();
	}

	public function callGetApiOptions(array $additional = []): Options
	{
		return $this->getApiOptions($additional);
	}

	/**
	 * Reads an option back out of the input, unfiltered, so a test can see what prepare() made of it.
	 *
	 * @param   string  $name  The option to read
	 *
	 * @return  mixed
	 * @since   3.2.0
	 */
	public function readOption(string $name): mixed
	{
		return $this->input->get($name, null, 'raw');
	}
}
