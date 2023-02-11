<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Vault;

use org\bovigo\vfs\vfsStream;
use Weph\ObsidianTools\Vault\Vault;
use Weph\ObsidianTools\Vault\VaultUsingFilesystem;

/**
 * @covers \Weph\ObsidianTools\Vault\VaultUsingFilesystem
 *
 * @uses   \Weph\ObsidianTools\Vault\Asset
 * @uses   \Weph\ObsidianTools\Vault\Note
 * @uses   \Weph\ObsidianTools\Vault\NoteNotFound
 * @uses   \Weph\ObsidianTools\Vault\MatchedNote
 * @uses   \Weph\ObsidianTools\Vault\Query
 */
final class VaultUsingFilesystemTest extends VaultTestCase
{
    private VaultUsingFilesystem $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $root = vfsStream::setup();

        $this->subject = VaultUsingFilesystem::atPath($root->url());
    }

    protected function subject(): Vault
    {
        return $this->subject;
    }
}
