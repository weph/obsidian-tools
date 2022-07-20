<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Actions;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Actions\GenerateJournalSummaries;
use Weph\ObsidianTools\Vault\MatchedNote;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\VaultUsingFilesystem;

/**
 * @covers \Weph\ObsidianTools\Actions\GenerateJournalSummaries
 *
 * @uses   \Weph\ObsidianTools\DailyNotes\CalendarWeekNotes
 * @uses   \Weph\ObsidianTools\DailyNotes\DailyNote
 * @uses   \Weph\ObsidianTools\DailyNotes\DailyNotes
 * @uses   \Weph\ObsidianTools\Vault\MatchedNote
 * @uses   \Weph\ObsidianTools\Vault\Note
 * @uses   \Weph\ObsidianTools\Vault\Query
 * @uses   \Weph\ObsidianTools\Vault\VaultUsingFilesystem
 */
final class GenerateJournalSummariesTest extends TestCase
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
    public function it_should_generate_an_overview_with_all_covered_years_and_months(): void
    {
        $this->vault->save(new Note('Notes/Daily Notes/2019-03-03.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-01.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-01.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-02-12.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2021-11-10.md', [], ''));

        (new GenerateJournalSummaries($this->vault))->run();

        $note = $this->vault->get('Notes/Daily Notes/Journal.md');
        self::assertInstanceOf(Note::class, $note);
        self::assertEquals(['parent' => '[[Index]]', 'tags' => ['generated']], $note->frontMatter);
        self::assertEquals(
            implode(
                "\n",
                [
                    '# Journal',
                    '',
                    '## 2022',
                    '[[2022-01|Januar]] / ',
                    '[[2022-02|Februar]]',
                    '',
                    '## 2021',
                    '[[2021-11|November]]',
                    '',
                    '## 2019',
                    '[[2019-03|März]]',
                    '',
                    '',
                ]
            ),
            $note->content
        );
    }

    /**
     * @test
     */
    public function it_should_create_a_monthly_overview_for_all_covered_months(): void
    {
        $this->vault->save(new Note('Notes/Daily Notes/2019-03-03.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-01.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-02-12.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2021-11-10.md', [], ''));

        (new GenerateJournalSummaries($this->vault))->run();

        $query = Query::create()->withLocation('Notes/Daily Notes')->withFilename('/\d{4}-\d{2}\.md/');
        $notes = $this->vault->notesMatching($query);
        self::assertEquals(
            [
                'Notes/Daily Notes/2019/03/2019-03.md',
                'Notes/Daily Notes/2021/11/2021-11.md',
                'Notes/Daily Notes/2022/01/2022-01.md',
                'Notes/Daily Notes/2022/02/2022-02.md',
            ],
            array_map(static fn (MatchedNote $v) => $v->note->path, $notes)
        );
    }

    /**
     * @test
     */
    public function the_monthly_overview_should_embed_every_daily_note_of_that_month(): void
    {
        $this->vault->save(new Note('Notes/Daily Notes/2019-01-02.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2019-01-04.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-01.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-09.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-12.md', [], ''));

        (new GenerateJournalSummaries($this->vault))->run();

        $note = $this->vault->get('Notes/Daily Notes/2022/01/2022-01.md');
        self::assertInstanceOf(Note::class, $note);
        self::assertNoteHasFrontmatter('parent', '[[Journal]]', $note);
        self::assertNoteHasFrontmatter('tags', ['generated', 'Journal/2022/01'], $note);
        self::assertEquals(
            implode(
                "\n",
                [
                    '# Januar 2022',
                    '',
                    '![[2022-01-01]]',
                    '![[2022-01-09]]',
                    '![[2022-01-12]]',
                    '',
                    '',
                ]
            ),
            $note->content
        );
    }

    /**
     * @test
     */
    public function the_monthly_overview_should_link_to_the_previous_and_next_month(): void
    {
        $this->vault->save(new Note('Notes/Daily Notes/2019-01-02.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2019-07-04.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2021-01-01.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-12.md', [], ''));

        (new GenerateJournalSummaries($this->vault))->run();

        self::assertNoteDoesNotHaveFrontmatterField('prev', $this->vault->get('Notes/Daily Notes/2019/01/2019-01.md'));
        self::assertNoteHasFrontmatter('next', '[[2019-07]]', $this->vault->get('Notes/Daily Notes/2019/01/2019-01.md'));

        self::assertNoteHasFrontmatter('prev', '[[2019-01]]', $this->vault->get('Notes/Daily Notes/2019/07/2019-07.md'));
        self::assertNoteHasFrontmatter('next', '[[2021-01]]', $this->vault->get('Notes/Daily Notes/2019/07/2019-07.md'));

        self::assertNoteHasFrontmatter('prev', '[[2019-07]]', $this->vault->get('Notes/Daily Notes/2021/01/2021-01.md'));
        self::assertNoteHasFrontmatter('next', '[[2022-01]]', $this->vault->get('Notes/Daily Notes/2021/01/2021-01.md'));

        self::assertNoteHasFrontmatter('prev', '[[2021-01]]', $this->vault->get('Notes/Daily Notes/2022/01/2022-01.md'));
        self::assertNoteDoesNotHaveFrontmatterField('next', $this->vault->get('Notes/Daily Notes/2022/01/2022-01.md'));
    }

    private static function assertNoteHasFrontmatter(string $field, mixed $value, mixed $note): void
    {
        self::assertInstanceOf(Note::class, $note);
        self::assertArrayHasKey($field, $note->frontMatter, 'Field does not exist in frontmatter');
        self::assertEquals($value, $note->frontMatter[$field] ?? null);
    }

    private static function assertNoteDoesNotHaveFrontmatterField(string $field, mixed $note): void
    {
        self::assertInstanceOf(Note::class, $note);
        self::assertArrayNotHasKey($field, $note->frontMatter, 'Field exist in frontmatter');
    }
}
