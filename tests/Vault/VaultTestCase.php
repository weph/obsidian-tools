<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Vault;

use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Vault\Asset;
use Weph\ObsidianTools\Vault\MatchedNote;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\NoteNotFound;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\Vault;

abstract class VaultTestCase extends TestCase
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
        $note = new Note('my-note.md', ['tags' => ['a', 'b', 'c']], '# My Note');

        $this->subject()->save($note);

        self::assertEquals($note, $this->subject()->get('my-note.md'));
    }

    /**
     * @test
     */
    public function a_note_can_be_stored_in_a_nested_structure(): void
    {
        $note = new Note('path/to/my-note.md', ['tags' => ['a', 'b', 'c']], '# My Note');

        $this->subject()->save($note);

        self::assertEquals($note, $this->subject()->get('path/to/my-note.md'));
    }

    /**
     * @test
     */
    public function save_should_overwrite_existing_note(): void
    {
        $location     = 'my-note.md';
        $originalNote = new Note($location, ['tags' => ['a', 'b', 'c']], '# My Note');
        $this->subject()->save($originalNote);

        $updatedNote = new Note($location, ['tags' => ['new-tag']], '# New Headline');
        $this->subject()->save($updatedNote);

        self::assertEquals($updatedNote, $this->subject()->get($location));
    }

    /**
     * @test
     */
    public function it_returns_all_saved_notes_and_assets(): void
    {
        $note1  = new Note('my-note1.md', ['tags' => ['a']], '# My Note 1');
        $note2  = new Note('my-note2.md', ['tags' => ['b']], '# My Note 2');
        $note3  = new Note('my-note3.md', ['tags' => ['c']], '# My Note 3');
        $asset1 = new Asset('my-asset-1', 'Asset Data 1');
        $asset2 = new Asset('my-asset-2', 'Asset Data 2');
        $this->saveAll($note1, $note2, $note3, $asset1, $asset2);

        self::assertEquals([$note1, $note2, $note3, $asset1, $asset2], $this->subject()->all());
    }

    /**
     * @param list<Note|Asset>  $notes
     * @param list<MatchedNote> $expected
     *
     * @test
     *
     * @dataProvider locationQueryExamples
     * @dataProvider filenameQueryExamples
     * @dataProvider matchingExamples
     */
    public function it_should_return_matching_notes(array $notes, Query $query, array $expected): void
    {
        $this->saveAll(...$notes);

        $result = $this->subject()->notesMatching($query);

        self::assertEquals($expected, $result);
    }

    /**
     * @return iterable<string, array{0: list<Note>, 1: Query, 2: list<MatchedNote>}>
     */
    public static function locationQueryExamples(): iterable
    {
        $rootNote   = new Note('note.md', [], '');
        $fooNote1   = new Note('foo/note.md', [], '');
        $fooFooNote = new Note('foo/foo/note.md', [], '');
        $fooBarNote = new Note('foo/bar/note.md', [], '');
        $fooBooNote = new Note('foo/boo/note.md', [], '');
        $notes      = [$rootNote, $fooNote1, $fooFooNote, $fooBarNote, $fooBooNote];

        yield 'No location matches everything' => [
            $notes,
            Query::create(),
            [
                new MatchedNote($rootNote, []),
                new MatchedNote($fooNote1, []),
                new MatchedNote($fooFooNote, []),
                new MatchedNote($fooBarNote, []),
                new MatchedNote($fooBooNote, []),
            ],
        ];

        yield 'Notes in location with subdirectory' => [
            $notes,
            Query::create()->withLocation('foo'),
            [
                new MatchedNote($fooNote1, []),
                new MatchedNote($fooFooNote, []),
                new MatchedNote($fooBarNote, []),
                new MatchedNote($fooBooNote, []),
            ],
        ];

        yield 'Notes in nested subdirectory' => [
            $notes,
            Query::create()->withLocation('foo/bar'),
            [new MatchedNote($fooBarNote, [])],
        ];

        yield 'Regex can be used' => [
            $notes,
            Query::create()->withLocation('|foo/b+|'),
            [new MatchedNote($fooBarNote, []), new MatchedNote($fooBooNote, [])],
        ];
    }

    /**
     * @return iterable<string, array{0: list<Note>, 1: Query, 2: list<MatchedNote>}>
     */
    public static function filenameQueryExamples(): iterable
    {
        $foo       = new Note('foo.md', [], '');
        $foobar    = new Note('foobar.md', [], '');
        $barFoo    = new Note('bar/foo.md', [], '');
        $barBarfoo = new Note('bar/barfoo.md', [], '');
        $notes     = [$foo, $foobar, $barFoo, $barBarfoo];

        yield 'Match exact name' => [
            $notes,
            Query::create()->withFilename('foo.md'),
            [
                new MatchedNote($foo, []),
                new MatchedNote($barFoo, []),
            ],
        ];

        yield 'Match regex' => [
            $notes,
            Query::create()->withFilename('/(foobar|barfoo).md/'),
            [
                new MatchedNote($foobar, []),
                new MatchedNote($barBarfoo, []),
            ],
        ];
    }

    /**
     * @return iterable<string, array{0: list<Note|Asset>, 1: Query, 2: list<MatchedNote>}>
     */
    public static function matchingExamples(): iterable
    {
        $note1 = new Note('note1.md', [], 'foo:foo bar:foo');
        $note2 = new Note('note2.md', [], 'foo:bar bar:bar');
        $note3 = new Note('note3.md', [], 'foo:foo1 bar:bar1 foo:foo2 bar:bar2');
        $notes = [$note1, $note2, $note3];

        yield 'Without groups' => [
            $notes,
            Query::create()->withContent('/foo:foo/'),
            [new MatchedNote($note1, []), new MatchedNote($note3, [])],
        ];

        yield 'Single group' => [
            $notes,
            Query::create()->withContent('/foo:([^\s]+)/'),
            [
                new MatchedNote($note1, [['foo']]),
                new MatchedNote($note2, [['bar']]),
                new MatchedNote($note3, [['foo1'], ['foo2']]),
            ],
        ];

        yield 'Multiple groups' => [
            $notes,
            Query::create()->withContent('/foo:([^\s]+) bar:([^\s]+)/'),
            [
                new MatchedNote($note1, [['foo', 'foo']]),
                new MatchedNote($note2, [['bar', 'bar']]),
                new MatchedNote($note3, [['foo1', 'bar1'], ['foo2', 'bar2']]),
            ],
        ];

        yield 'Named groups' => [
            $notes,
            Query::create()->withContent('/foo:(?P<foo>[^\s]+) bar:(?P<bar>[^\s]+)/'),
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

        yield 'Assets should not match' => [
            [$note1, new Asset('asset', 'foo:foo bar:foo'), $note3],
            Query::create()->withContent('/foo:foo/'),
            [new MatchedNote($note1, []), new MatchedNote($note3, [])],
        ];
    }

    /**
     * @test
     */
    public function subsequent_queries_should_return_the_expected_results(): void
    {
        $rootNote = new Note('note.md', [], '');
        $fooNote  = new Note('foo/note.md', [], '');
        $barNote  = new Note('bar/note.md', [], '');
        $this->saveAll($rootNote, $fooNote, $barNote);

        self::assertEquals(
            [new MatchedNote($fooNote, [])],
            $this->subject()->notesMatching(Query::create()->withLocation('foo'))
        );

        self::assertEquals(
            [new MatchedNote($barNote, [])],
            $this->subject()->notesMatching(Query::create()->withLocation('bar'))
        );

        self::assertEquals([$rootNote, $fooNote, $barNote], $this->subject()->all());
    }

    abstract protected function subject(): Vault;

    private function saveAll(Note|Asset ...$notes): void
    {
        foreach ($notes as $note) {
            $this->subject()->save($note);
        }
    }
}
