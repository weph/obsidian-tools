<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Actions;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Actions\GenerateWeeklySummary;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\VaultUsingFilesystem;

/**
 * @covers \Weph\ObsidianTools\Actions\GenerateWeeklySummary
 *
 * @uses   \Weph\ObsidianTools\DailyNotes\DailyNotes
 * @uses   \Weph\ObsidianTools\Vault\MatchedNote
 * @uses   \Weph\ObsidianTools\Vault\Note
 * @uses   \Weph\ObsidianTools\Vault\Query
 * @uses   \Weph\ObsidianTools\Vault\VaultUsingFilesystem
 */
final class GenerateWeeklySummaryTest extends TestCase
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
    public function it_should_embed_every_daily_note_per_calendar_week(): void
    {
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-01.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-02.md', [], ''));

        $this->vault->save(new Note('Notes/Daily Notes/2022-01-03.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-04.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-05.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-06.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-07.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-08.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-09.md', [], ''));

        $this->vault->save(new Note('Notes/Daily Notes/2022-01-10.md', [], ''));

        (new GenerateWeeklySummary($this->vault))->run();

        $note = $this->vault->get('Notes/Daily Notes/2021/2021-W52.md');
        self::assertInstanceOf(Note::class, $note);
        self::assertEquals(
            implode(
                "\n",
                [
                    '# 2021 - KW 52',
                    '',
                    '![[2022-01-01]]',
                    '![[2022-01-02]]',
                    '',
                    '',
                ]
            ),
            $note->content
        );

        $note = $this->vault->get('Notes/Daily Notes/2022/2022-W01.md');
        self::assertInstanceOf(Note::class, $note);
        self::assertEquals(
            implode(
                "\n",
                [
                    '# 2022 - KW 1',
                    '',
                    '![[2022-01-03]]',
                    '![[2022-01-04]]',
                    '![[2022-01-05]]',
                    '![[2022-01-06]]',
                    '![[2022-01-07]]',
                    '![[2022-01-08]]',
                    '![[2022-01-09]]',
                    '',
                    '',
                ]
            ),
            $note->content
        );

        $note = $this->vault->get('Notes/Daily Notes/2022/2022-W02.md');
        self::assertInstanceOf(Note::class, $note);
        self::assertEquals(
            implode(
                "\n",
                [
                    '# 2022 - KW 2',
                    '',
                    '![[2022-01-10]]',
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
    public function it_should_link_to_the_previous_and_next_calendar_week(): void
    {
        $this->vault->save(new Note('Notes/Daily Notes/2022-05-01.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-06-01.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-06-06.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-06-30.md', [], ''));

        (new GenerateWeeklySummary($this->vault))->run();

        self::assertNoteDoesNotHaveFrontmatterField('prev', $this->vault->get('Notes/Daily Notes/2022/2022-W17.md'));
        self::assertNoteHasFrontmatter('next', '[[2022-W22]]', $this->vault->get('Notes/Daily Notes/2022/2022-W17.md'));

        self::assertNoteHasFrontmatter('prev', '[[2022-W17]]', $this->vault->get('Notes/Daily Notes/2022/2022-W22.md'));
        self::assertNoteHasFrontmatter('next', '[[2022-W23]]', $this->vault->get('Notes/Daily Notes/2022/2022-W22.md'));

        self::assertNoteHasFrontmatter('prev', '[[2022-W22]]', $this->vault->get('Notes/Daily Notes/2022/2022-W23.md'));
        self::assertNoteHasFrontmatter('next', '[[2022-W26]]', $this->vault->get('Notes/Daily Notes/2022/2022-W23.md'));

        self::assertNoteHasFrontmatter('prev', '[[2022-W23]]', $this->vault->get('Notes/Daily Notes/2022/2022-W26.md'));
        self::assertNoteDoesNotHaveFrontmatterField('next', $this->vault->get('Notes/Daily Notes/2022/2022-W26.md'));
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
