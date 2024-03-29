<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Vault;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Vault\Note;

#[CoversClass(Note::class)]
final class NoteTest extends TestCase
{
    #[Test]
    public function note_name_should_be_derived_from_filename(): void
    {
        $note = new Note('path/to/My Note.md', [], '');

        self::assertSame('My Note', $note->name);
    }

    #[Test]
    public function withFrontMatterField_should_return_a_clone_with_replaced_front_matter_field(): void
    {
        $note = new Note('note.md', ['author' => 'Joe', 'date' => '2022-02-03'], 'Content');

        $result = $note->withFrontMatterField('author', 'Jack');

        self::assertEquals(
            new Note('note.md', ['author' => 'Jack', 'date' => '2022-02-03'], 'Content'),
            $result
        );
    }
}
