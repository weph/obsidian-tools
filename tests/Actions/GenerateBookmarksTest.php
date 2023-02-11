<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Actions;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Actions\GenerateBookmarks;
use Weph\ObsidianTools\DailyNotes\DailyNotes;
use Weph\ObsidianTools\Markdown\Table;
use Weph\ObsidianTools\Vault\MatchedNote;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\VaultUsingFilesystem;

#[CoversClass(GenerateBookmarks::class)]
#[UsesClass(DailyNotes::class)]
#[UsesClass(MatchedNote::class)]
#[UsesClass(Note::class)]
#[UsesClass(Query::class)]
#[UsesClass(VaultUsingFilesystem::class)]
#[UsesClass(Table::class)]
final class GenerateBookmarksTest extends TestCase
{
    private VaultUsingFilesystem $vault;

    protected function setUp(): void
    {
        parent::setUp();

        $root = vfsStream::setup();

        $this->vault = VaultUsingFilesystem::atPath($root->url());
    }

    #[Test]
    public function it_created_a_table_of_all_bookmarks(): void
    {
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-01.md', [], '- [Link 1](https://link1.test): A description #bookmark #foo'));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-02.md', [], '- [Link 2](https://link2.test): Another description #bookmark #bar'));
        $this->vault->save(new Note('Notes/Daily Notes/2022-02-01.md', [], '- [Link 3](https://link3.test): Yet another description #bookmark #foo #bar'));

        (new GenerateBookmarks($this->vault))->run();

        $note = $this->vault->get('Notes/Bookmarks.md');
        self::assertInstanceOf(Note::class, $note);
        self::assertEquals(['parent' => '[[Index]]', 'tags' => ['generated']], $note->frontMatter);
        self::assertEquals(
            implode(
                "\n",
                [
                    '# Bookmarks',
                    '',
                    '| Quelle         | Link                         | Beschreibung            | Tags      |',
                    '| -------------- | ---------------------------- | ----------------------- | --------- |',
                    '| [[2022-01-01]] | [Link 1](https://link1.test) | A description           | #foo      |',
                    '| [[2022-01-02]] | [Link 2](https://link2.test) | Another description     | #bar      |',
                    '| [[2022-02-01]] | [Link 3](https://link3.test) | Yet another description | #foo #bar |',
                    '',
                ]
            ),
            $note->content
        );
    }
}
