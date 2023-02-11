<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Actions;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Actions\GenerateReadingList;
use Weph\ObsidianTools\Markdown\Table;
use Weph\ObsidianTools\Vault\MatchedNote;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\VaultUsingFilesystem;

#[CoversClass(GenerateReadingList::class)]
#[UsesClass(Table::class)]
#[UsesClass(MatchedNote::class)]
#[UsesClass(Note::class)]
#[UsesClass(Query::class)]
#[UsesClass(VaultUsingFilesystem::class)]
final class ReadingListGeneratorTest extends TestCase
{
    private VaultUsingFilesystem $vault;

    protected function setUp(): void
    {
        parent::setUp();

        $root = vfsStream::setup();

        $this->vault = VaultUsingFilesystem::atPath($root->url());
    }

    #[Test]
    public function it_should_build_a_table_of_all_book_the_have_been_started_or_finished(): void
    {
        $this->vault->save(new Note('a.md', [], '- Started reading "BDD in Action" #book/started'));
        $this->vault->save(new Note('b.md', [], '- Finished reading "Test-Driven Development by Example" #book/finished'));
        $this->vault->save(new Note('c.md', [], '- Started listening to "Digital Minimalism" #audiobook/started'));

        (new GenerateReadingList($this->vault))->run();

        $note = $this->vault->get('Notes/Leseliste.md');
        self::assertInstanceOf(Note::class, $note);
        self::assertEquals(['parent' => '[[Index]]', 'tags' => ['generated']], $note->frontMatter);
        self::assertEquals(
            implode(
                "\n",
                [
                    '# Leseliste',
                    '',
                    '| Titel                              |',
                    '| ---------------------------------- |',
                    '| BDD in Action                      |',
                    '| Digital Minimalism                 |',
                    '| Test-Driven Development by Example |',
                    '',
                ]
            ),
            $note->content
        );
    }

    #[Test]
    public function it_should_link_to_the_corresponding_note(): void
    {
        $this->vault->save(new Note('a.md', [], '- Started reading [[BDD in Action]] #book/started'));
        $this->vault->save(new Note('b.md', [], '- Started reading [[Test-Driven Development by Example]] #book/finished'));

        (new GenerateReadingList($this->vault))->run();

        $note = $this->vault->get('Notes/Leseliste.md');
        self::assertInstanceOf(Note::class, $note);
        self::assertEquals(['parent' => '[[Index]]', 'tags' => ['generated']], $note->frontMatter);
        self::assertEquals(
            implode(
                "\n",
                [
                    '# Leseliste',
                    '',
                    '| Titel                                  |',
                    '| -------------------------------------- |',
                    '| [[BDD in Action]]                      |',
                    '| [[Test-Driven Development by Example]] |',
                    '',
                ]
            ),
            $note->content
        );
    }

    #[Test]
    public function it_should_include_book_notes(): void
    {
        $this->vault->save(new Note('Sources/Books - Non Fiction/Test-Driven Development by Example.md', [], ''));
        $this->vault->save(new Note('Sources/Books - Non Fiction/Deep Work.md', [], ''));
        $this->vault->save(new Note('Sources/Books - Non Fiction/BDD in Action.md', [], ''));
        $this->vault->save(new Note('Sources/Books - Fiction/Shining.md', [], ''));

        (new GenerateReadingList($this->vault))->run();

        $note = $this->vault->get('Notes/Leseliste.md');
        self::assertInstanceOf(Note::class, $note);
        self::assertEquals(['parent' => '[[Index]]', 'tags' => ['generated']], $note->frontMatter);
        self::assertEquals(
            implode(
                "\n",
                [
                    '# Leseliste',
                    '',
                    '| Titel                                  |',
                    '| -------------------------------------- |',
                    '| [[BDD in Action]]                      |',
                    '| [[Deep Work]]                          |',
                    '| [[Shining]]                            |',
                    '| [[Test-Driven Development by Example]] |',
                    '',
                ]
            ),
            $note->content
        );
    }

    #[Test]
    public function books_should_be_ordered_alphabetically(): void
    {
        $this->vault->save(new Note('a.md', [], '- "The Art of War" #book/started'));
        $this->vault->save(new Note('b.md', [], '- "Ulysses" #book/started'));
        $this->vault->save(new Note('c.md', [], '- "A Tale of Two Cities" #book/started'));
        $this->vault->save(new Note('d.md', [], '- "Don Quixote" #book/started'));

        (new GenerateReadingList($this->vault))->run();

        $note = $this->vault->get('Notes/Leseliste.md');
        self::assertInstanceOf(Note::class, $note);
        self::assertEquals(['parent' => '[[Index]]', 'tags' => ['generated']], $note->frontMatter);
        self::assertEquals(
            implode(
                "\n",
                [
                    '# Leseliste',
                    '',
                    '| Titel                |',
                    '| -------------------- |',
                    '| A Tale of Two Cities |',
                    '| Don Quixote          |',
                    '| The Art of War       |',
                    '| Ulysses              |',
                    '',
                ]
            ),
            $note->content
        );
    }
}
