<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Actions;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Actions\UpdateBreadcrumbs;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\VaultUsingFilesystem;

/**
 * @covers \Weph\ObsidianTools\Actions\UpdateBreadcrumbs
 *
 * @uses   \Weph\ObsidianTools\DailyNotes\CalendarWeekNotes
 * @uses   \Weph\ObsidianTools\DailyNotes\DailyNote
 * @uses   \Weph\ObsidianTools\DailyNotes\DailyNotes
 * @uses   \Weph\ObsidianTools\Vault\MatchedNote
 * @uses   \Weph\ObsidianTools\Vault\Note
 * @uses   \Weph\ObsidianTools\Vault\Query
 * @uses   \Weph\ObsidianTools\Vault\VaultUsingFilesystem
 */
final class UpdateBreadcrumbsTest extends TestCase
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
    public function it_should_set_breadcrumbs_frontmatter_on_daily_notes(): void
    {
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-01.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-02.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-02-01.md', [], ''));

        (new UpdateBreadcrumbs($this->vault))->run();

        self::assertFrontMatter(['parent' => '[[2022-01]]', 'next' => '[[2022-01-02]]'], 'Notes/Daily Notes/2022-01-01.md');
        self::assertFrontMatter(['parent' => '[[2022-01]]', 'prev' => '[[2022-01-01]]', 'next' => '[[2022-02-01]]'], 'Notes/Daily Notes/2022-01-02.md');
        self::assertFrontMatter(['parent' => '[[2022-02]]', 'prev' => '[[2022-01-02]]'], 'Notes/Daily Notes/2022-02-01.md');
    }

    private function assertFrontMatter(array $frontMatter, string $noteLocation): void
    {
        $note = $this->vault->get($noteLocation);
        Assert::assertInstanceOf(Note::class, $note, $noteLocation . ' is not a note');
        Assert::assertEquals($frontMatter, $note->frontMatter);
    }
}
