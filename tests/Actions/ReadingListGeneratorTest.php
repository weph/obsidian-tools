<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Actions;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Actions\GenerateReadingList;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\VaultUsingFilesystem;

/**
 * @covers \Weph\ObsidianTools\Actions\GenerateReadingList
 *
 * @uses   \Weph\ObsidianTools\Markdown\Table
 * @uses   \Weph\ObsidianTools\Vault\MatchedNote
 * @uses   \Weph\ObsidianTools\Vault\Note
 * @uses   \Weph\ObsidianTools\Vault\Query
 * @uses   \Weph\ObsidianTools\Vault\VaultUsingFilesystem
 */
final class ReadingListGeneratorTest extends TestCase
{
    private VaultUsingFilesystem $vault;

    protected function setUp(): void
    {
        parent::setUp();

        $root = vfsStream::setup();

        $this->vault = VaultUsingFilesystem::atPath($root->url());
    }

    /**
     * @test
     */
    public function it_should_build_a_table_of_all_books(): void
    {
        $this->vault->save(new Note('Notes/Quellen/Bücher/Test-Driven Development by Example.md', [], ''));
        $this->vault->save(new Note('Notes/Quellen/Bücher/Deep Work.md', [], ''));
        $this->vault->save(new Note('Notes/Quellen/Bücher/BDD in Action.md', [], ''));

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
                    '| [[Test-Driven Development by Example]] |',
                    '| [[Deep Work]]                          |',
                    '| [[BDD in Action]]                      |',
                    '',
                ]
            ),
            $note->content
        );
    }
}
