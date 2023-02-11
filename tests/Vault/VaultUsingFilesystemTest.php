<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Vault;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Weph\ObsidianTools\Vault\Asset;
use Weph\ObsidianTools\Vault\MatchedNote;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\NoteNotFound;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\Vault;
use Weph\ObsidianTools\Vault\VaultUsingFilesystem;

#[CoversClass(VaultUsingFilesystem::class)]
#[UsesClass(Asset::class)]
#[UsesClass(Note::class)]
#[UsesClass(NoteNotFound::class)]
#[UsesClass(MatchedNote::class)]
#[UsesClass(Query::class)]
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
