<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools;

use Weph\ObsidianTools\Note;
use Weph\ObsidianTools\NoteNotFound;
use Weph\ObsidianTools\Vault;
use PHPUnit\Framework\TestCase;

abstract class VaultTest extends TestCase
{
    /**
     * @test
     */
    public function a_new_vault_should_be_empty(): void
    {
        self::assertEquals([], $this->subject()->all());
    }

    /**
     * @test
     */
    public function loading_a_non_existing_note_should_result_in_not_found_exception(): void
    {
        $this->expectExceptionObject(NoteNotFound::atLocation('invalid/note'));

        $this->subject()->get('invalid/note');
    }

    /**
     * @test
     */
    public function it_returns_a_saved_note(): void
    {
        $note = new Note('my-note', ['tags' => ['a', 'b', 'c']], '# My Note');

        $this->subject()->save($note);

        self::assertEquals($note, $this->subject()->get('my-note'));
    }

    /**
     * @test
     */
    public function save_should_overwrite_existing_note(): void
    {
        $location = 'my-note';
        $originalNote = new Note($location, ['tags' => ['a', 'b', 'c']], '# My Note');
        $this->subject()->save($originalNote);

        $updatedNote = new Note($location, ['tags' => ['new-tag']], '# New Headline');
        $this->subject()->save($updatedNote);

        self::assertEquals($updatedNote, $this->subject()->get($location));
    }

    /**
     * @test
     */
    public function it_returns_all_saved_notes(): void
    {
        $note1 = new Note('my-note1', ['tags' => ['a']], '# My Note 1');
        $note2 = new Note('my-note2', ['tags' => ['b']], '# My Note 2');
        $note3 = new Note('my-note3', ['tags' => ['c']], '# My Note 3');
        $this->saveAll($note1, $note2, $note3);

        self::assertEquals([$note1, $note2, $note3], $this->subject()->all());
    }

    private function saveAll(Note ...$notes): void
    {
        foreach ($notes as $note) {
            $this->subject()->save($note);
        }
    }

    abstract protected function subject(): Vault;
}
