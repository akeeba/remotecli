<?php
/*
 * @package    AkeebaRemoteCLI
 * @copyright  Copyright (c)2008-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU General Public License version 3, or later
 */

namespace Akeeba\RemoteCLI\Tests\Integration\Tests;

use Akeeba\RemoteCLI\Tests\Integration\E2ETestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Which credential is presented, and what happens when the site refuses it.
 *
 * @since 3.2.0
 */
class AuthenticationTest extends E2ETestCase
{
	#[TestDox('A Joomla API Token travels as a request header, never in the URL')]
	public function testTokenTravelsAsAHeader(): void
	{
		$this->assertSucceeded($this->runWithToken(['test']));

		[$type, $value] = $this->fixture->lastCredential();

		$this->assertSame('token', $type);
		$this->assertSame($this->token(), $value);

		/**
		 * The point of the v3 API's header authentication is that the credential stays out of the server's access
		 * log, so it has to stay out of the URL. Asserting it against the requests the server actually received is
		 * the only way to prove that; a client can say anything about itself.
		 */
		foreach ($this->fixture->requests() as $request)
		{
			$this->assertStringNotContainsString($this->token(), $request['uri']);
		}
	}

	#[TestDox('The Secret Word travels as a request header on the v3 API')]
	public function testSecretTravelsAsAHeaderOnV3(): void
	{
		$this->assertSucceeded($this->runWithSecret(['test']));

		[$type, $value] = $this->fixture->lastCredential();

		$this->assertSame('secret', $type);
		$this->assertSame($this->secret(), $value);

		foreach ($this->fixture->requests() as $request)
		{
			$this->assertStringNotContainsString($this->secret(), $request['uri']);
		}
	}

	#[TestDox('Given both credentials, the token is the one presented')]
	public function testTokenIsPreferredOverTheSecretWord(): void
	{
		$result = $this->cli->run(
			[
				'test',
				'--host=' . $this->site(),
				'--secret=' . $this->secret(),
				'--token=' . $this->token(),
			]
		);

		$this->assertSucceeded($result);

		[$type, $value] = $this->fixture->lastCredential();

		$this->assertSame('token', $type, 'The token should win over the Secret Word.');
		$this->assertSame($this->token(), $value);
	}

	#[TestDox('A wrong Secret Word does not stop a good token being used')]
	public function testGoodTokenSurvivesAWrongSecretWord(): void
	{
		$result = $this->cli->run(
			[
				'test',
				'--host=' . $this->site(),
				'--secret=utterly-wrong',
				'--token=' . $this->token(),
			]
		);

		$this->assertSucceeded($result);
		$this->assertSame('token', $this->fixture->lastCredential()[0]);
	}

	#[TestDox('A token pasted into --secret is tried as a token')]
	public function testTokenPastedIntoTheSecretOptionStillWorks(): void
	{
		/**
		 * Users who have only ever had one credential field will paste a token into it. Nothing is lost by guessing
		 * wrong — one failed request — and pasting a token where a Secret Word used to go simply works.
		 */
		$result = $this->cli->run(['test', '--host=' . $this->site(), '--secret=' . $this->token()]);

		$this->assertSucceeded($result);
		$this->assertSame('token', $this->fixture->lastCredential()[0]);
	}

	#[TestDox('A wrong credential is reported as an authentication error, not as an unreachable site')]
	public function testWrongCredentialIsAnAuthenticationError(): void
	{
		$result = $this->cli->run(['test', '--host=' . $this->site(), '--secret=utterly-wrong']);

		// Error #42: authentication error. Distinct from #36, which would send the user off checking their host name.
		$this->assertFailedWithCode($result, 42, 'Authentication error');
	}

	#[TestDox('No credential at all is refused before any request is made')]
	public function testNoCredentialIsRefusedLocally(): void
	{
		$result = $this->cli->run(['test', '--host=' . $this->site()]);

		// Error #37: you did not specify a credential to authenticate with.
		$this->assertFailedWithCode($result, 37);

		/**
		 * The refusal has to happen before anything is sent. A tool which asks the site first has told the site it is
		 * there, and made the user wait for a round trip to be told about their own typo.
		 */
		$this->assertSame([], $this->fixture->requests(), 'Nothing should have been sent to the site.');
	}

	#[TestDox('A valueless --token is refused rather than sent as a one-character credential')]
	public function testValuelessTokenIsRefused(): void
	{
		$result = $this->cli->run(['test', '--host=' . $this->site(), '--token']);

		$this->assertFailedWithCode($result, 37);
		$this->assertSame([], $this->fixture->requests());
	}

	#[TestDox('A restricted token may take a backup')]
	public function testRestrictedTokenMayBackUp(): void
	{
		$result = $this->cli->run(
			['backup', '--host=' . $this->site(), '--token=' . $this->restrictedToken(), '--profile=1']
		);

		$this->assertSucceeded($result);
	}

	#[TestDox('A restricted token is refused a method its account may not call')]
	public function testRestrictedTokenIsRefusedDelete(): void
	{
		$result = $this->cli->run(
			['delete', '--host=' . $this->site(), '--token=' . $this->restrictedToken(), '--id=1']
		);

		/**
		 * Error #43, distinct from #42. The token is fine and we did log in with it; the Joomla user account behind it
		 * simply may not do this. Telling the user to check their credential instead would send them nowhere.
		 */
		$this->assertFailedWithCode($result, 43, 'not allowed');
	}

	#[TestDox('A method a restricted token is refused leaves the record alone')]
	public function testRefusedDeleteDoesNotDelete(): void
	{
		$this->cli->run(['delete', '--host=' . $this->site(), '--token=' . $this->restrictedToken(), '--id=1']);

		/**
		 * The refusal is easy to fake; the row that was not deleted is not. Asking the site afterwards is what proves
		 * the operation did not happen, rather than merely that a message was printed about it.
		 */
		$listing = $this->runWithToken(['listbackups', '-m', '--quiet']);

		$this->assertSucceeded($listing);
		$this->assertStringContainsString('A single part backup', $listing->output());
	}
}
