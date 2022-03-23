<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools;

use org\bovigo\vfs\vfsStream;
use Weph\ObsidianTools\Vault;
use Weph\ObsidianTools\VaultUsingFilesystem;

/**
 * @covers \Weph\ObsidianTools\VaultUsingFilesystem
 * @uses   \Weph\ObsidianTools\Note
 * @uses   \Weph\ObsidianTools\NoteNotFound
 * @uses   \Weph\ObsidianTools\MatchedNote
 * @uses   \Weph\ObsidianTools\Query
 */
final class VaultUsingFilesystemTest extends VaultTest
{
    private VaultUsingFilesystem $subject;

    protected function subject(): Vault
    {
        return $this->subject;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $root = vfsStream::setup();

        $this->subject = VaultUsingFilesystem::atPath($root->url());
    }
}
