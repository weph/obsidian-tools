<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Vault;

use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Vault\MatchedNote;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\NoteNotFound;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\Vault;

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
        $location     = 'my-note';
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

    /**
     * @param MatchedNote $notes
     * @param MatchedNote $expected
     *
     * @test
     * @dataProvider matchingExamples
     */
    public function it_should_return_matching_notes(array $notes, Query $query, array $expected): void
    {
        $this->saveAll(...$notes);

        $result = $this->subject()->notesMatching($query);

        self::assertEquals($expected, $result);
    }

    public function matchingExamples(): iterable
    {
        $note1 = new Note('note1', [], 'foo:foo bar:foo');
        $note2 = new Note('note2', [], 'foo:bar bar:bar');
        $note3 = new Note('note3', [], 'foo:foo1 bar:bar1 foo:foo2 bar:bar2');
        $notes = [$note1, $note2, $note3];

        yield 'Without groups' => [
            $notes,
            new Query('/foo:foo/'),
            [new MatchedNote($note1, []), new MatchedNote($note3, [])],
        ];

        yield 'Single group' => [
            $notes,
            new Query('/foo:([^\s]+)/'),
            [
                new MatchedNote($note1, [['foo']]),
                new MatchedNote($note2, [['bar']]),
                new MatchedNote($note3, [['foo1'], ['foo2']]),
            ],
        ];

        yield 'Multiple groups' => [
            $notes,
            new Query('/foo:([^\s]+) bar:([^\s]+)/'),
            [
                new MatchedNote($note1, [['foo', 'foo']]),
                new MatchedNote($note2, [['bar', 'bar']]),
                new MatchedNote($note3, [['foo1', 'bar1'], ['foo2', 'bar2']]),
            ],
        ];

        yield 'Named groups' => [
            $notes,
            new Query('/foo:(?P<foo>[^\s]+) bar:(?P<bar>[^\s]+)/'),
            [
                new MatchedNote(
                    $note1,
                    [[0 => 'foo', 1 => 'foo', 'foo' => 'foo', 'bar' => 'foo']]
                ),
                new MatchedNote(
                    $note2,
                    [[0 => 'bar', 1 => 'bar', 'foo' => 'bar', 'bar' => 'bar']]
                ),
                new MatchedNote($note3,
                    [
                        [0 => 'foo1', 1 => 'bar1', 'foo' => 'foo1', 'bar' => 'bar1'],
                        [0 => 'foo2', 1 => 'bar2', 'foo' => 'foo2', 'bar' => 'bar2'],
                    ]
                ),
            ],
        ];
    }

    abstract protected function subject(): Vault;

    private function saveAll(Note ...$notes): void
    {
        foreach ($notes as $note) {
            $this->subject()->save($note);
        }
    }
}
