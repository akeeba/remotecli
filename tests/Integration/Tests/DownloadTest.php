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
 * Downloading backup archives.
 *
 * The archives the fixture serves are deterministic, so a test can work out what a part should contain without
 * downloading it twice. That is what turns "the download reported success" into "the right bytes arrived", which is
 * the only assertion worth making about a backup tool.
 *
 * @since 3.2.0
 */
class DownloadTest extends E2ETestCase
{
	private const DOWNLOAD_DIR = '/tmp/arccli-download';

	protected function setUp(): void
	{
		parent::setUp();

		$this->cli->shell(sprintf('rm -rf %s && mkdir -p %s', self::DOWNLOAD_DIR, self::DOWNLOAD_DIR));
	}

	/**
	 * The bytes the fixture serves for one archive part. Kept in step with fixture_archive_bytes().
	 */
	private function expectedBytes(int $recordId, int $part): string
	{
		return str_repeat(sprintf('AKEEBA-%d-%02d;', $recordId, $part), 4096);
	}

	private function downloadedFiles(): array
	{
		$result = $this->cli->shell(sprintf('ls -1 %s 2>/dev/null', self::DOWNLOAD_DIR));

		return array_values(array_filter(explode("\n", trim($result->stdout))));
	}

	private function contentsOf(string $name): string
	{
		return $this->cli->shell(sprintf('cat %s/%s', self::DOWNLOAD_DIR, escapeshellarg($name)))->stdout;
	}

	#[TestDox('A single part archive downloads with exactly the right bytes')]
	public function testSinglePartDownload(): void
	{
		$result = $this->runWithToken(['download', '--id=1', '--dlpath=' . self::DOWNLOAD_DIR]);

		$this->assertSucceeded($result);
		$this->assertSame(['single-2026-01-01.jpa'], $this->downloadedFiles());
		$this->assertSame($this->expectedBytes(1, 1), $this->contentsOf('single-2026-01-01.jpa'));
	}

	#[TestDox('Every part of a multipart archive downloads, each with its own bytes')]
	public function testMultipartDownload(): void
	{
		$result = $this->runWithToken(['download', '--id=2', '--dlpath=' . self::DOWNLOAD_DIR]);

		$this->assertSucceeded($result);

		$this->assertSame(
			['multi-2026-01-02.j01', 'multi-2026-01-02.j02', 'multi-2026-01-02.jpa'],
			$this->downloadedFiles()
		);

		/**
		 * Each part has to hold its own bytes, not three copies of the first. Only comparing the contents catches a
		 * client which builds the part URL once and reuses it.
		 */
		$this->assertSame($this->expectedBytes(2, 1), $this->contentsOf('multi-2026-01-02.j01'));
		$this->assertSame($this->expectedBytes(2, 2), $this->contentsOf('multi-2026-01-02.j02'));
		$this->assertSame($this->expectedBytes(2, 3), $this->contentsOf('multi-2026-01-02.jpa'));
	}

	#[TestDox('--part downloads only the part asked for')]
	public function testSinglePartOfAMultipartArchive(): void
	{
		$result = $this->runWithToken(['download', '--id=2', '--part=2', '--dlpath=' . self::DOWNLOAD_DIR]);

		$this->assertSucceeded($result);
		$this->assertSame(['multi-2026-01-02.j02'], $this->downloadedFiles());
		$this->assertSame($this->expectedBytes(2, 2), $this->contentsOf('multi-2026-01-02.j02'));
	}

	#[TestDox('The chunked download mode reassembles the same archive')]
	public function testChunkedDownload(): void
	{
		/**
		 * Chunked mode fetches the archive a range at a time, which is the mode for servers that cannot stream a large
		 * file in one response. A chunk size of 1MiB against a 48KiB part still exercises the ranged request path.
		 */
		$result = $this->runWithToken(
			['download', '--id=1', '--dlmode=chunk', '--chunk_size=1', '--dlpath=' . self::DOWNLOAD_DIR]
		);

		$this->assertSucceeded($result);
		$this->assertSame($this->expectedBytes(1, 1), $this->contentsOf('single-2026-01-01.jpa'));
	}

	#[TestDox('--delete removes the archives from the server after downloading them')]
	public function testDownloadAndDelete(): void
	{
		$result = $this->runWithToken(['download', '--id=1', '--delete', '--dlpath=' . self::DOWNLOAD_DIR]);

		$this->assertSucceeded($result);
		$this->assertSame($this->expectedBytes(1, 1), $this->contentsOf('single-2026-01-01.jpa'));

		/**
		 * The archive has to be gone from the server, and the record has to still be there. Asking the site is what
		 * proves it; the download's own success message says nothing about the far end.
		 */
		$listing = $this->runWithToken(['listbackups', '-m', '--quiet']);

		$this->assertStringContainsString('A single part backup', $listing->output());
		$this->assertStringContainsString('obsolete', $listing->output());
	}

	#[TestDox('Downloading without an ID is refused')]
	public function testDownloadWithoutAnId(): void
	{
		$result = $this->runWithToken(['download', '--dlpath=' . self::DOWNLOAD_DIR]);

		$this->assertNotSame(0, $result->exitCode, $result->describe());
		$this->assertSame([], $this->downloadedFiles());
	}

	#[TestDox('A restricted token cannot download, and no file is written')]
	public function testRestrictedTokenCannotDownload(): void
	{
		$result = $this->cli->run(
			[
				'download',
				'--host=' . $this->site(),
				'--token=' . $this->restrictedToken(),
				'--id=1',
				'--dlpath=' . self::DOWNLOAD_DIR,
			]
		);

		$this->assertNotSame(0, $result->exitCode, $result->describe());

		/**
		 * A refusal is easy to fake; an archive which was never written is not. A client which streamed the body of a
		 * 403 into the file would leave a plausible-looking archive full of an error message.
		 */
		foreach ($this->downloadedFiles() as $file)
		{
			$this->assertNotSame(
				$this->expectedBytes(1, 1),
				$this->contentsOf($file),
				'The archive must not have been downloaded.'
			);
		}
	}
}
