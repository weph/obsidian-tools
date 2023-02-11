<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Actions;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Actions\UpdateBreadcrumbs;
use Weph\ObsidianTools\DailyNotes\CalendarWeekNotes;
use Weph\ObsidianTools\DailyNotes\DailyNote;
use Weph\ObsidianTools\DailyNotes\DailyNotes;
use Weph\ObsidianTools\Vault\MatchedNote;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\VaultUsingFilesystem;

#[CoversClass(UpdateBreadcrumbs::class)]
#[UsesClass(CalendarWeekNotes::class)]
#[UsesClass(DailyNote::class)]
#[UsesClass(DailyNotes::class)]
#[UsesClass(MatchedNote::class)]
#[UsesClass(Note::class)]
#[UsesClass(Query::class)]
#[UsesClass(VaultUsingFilesystem::class)]
final class UpdateBreadcrumbsTest extends TestCase
{
    private VaultUsingFilesystem $vault;

    protected function setUp(): void
    {
        parent::setUp();

        $root = vfsStream::setup();

        $this->vault = VaultUsingFilesystem::atPath($root->url());
    }

    #[Test]
    public function it_should_set_breadcrumbs_frontmatter_on_daily_notes(): void
    {
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-01.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-02.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-02-01.md', [], ''));

        (new UpdateBreadcrumbs($this->vault))->run();

        self::assertFrontMatter(['parent' => ['[[2021-W52]]', '[[2022-01]]'], 'next' => '[[2022-01-02]]'], 'Notes/Daily Notes/2022-01-01.md');
        self::assertFrontMatter(['parent' => ['[[2021-W52]]', '[[2022-01]]'], 'prev' => '[[2022-01-01]]', 'next' => '[[2022-02-01]]'], 'Notes/Daily Notes/2022-01-02.md');
        self::assertFrontMatter(['parent' => ['[[2022-W05]]', '[[2022-02]]'], 'prev' => '[[2022-01-02]]'], 'Notes/Daily Notes/2022-02-01.md');
    }

    private function assertFrontMatter(array $frontMatter, string $noteLocation): void
    {
        $note = $this->vault->get($noteLocation);
        Assert::assertInstanceOf(Note::class, $note, $noteLocation . ' is not a note');
        Assert::assertEquals($frontMatter, $note->frontMatter);
    }
}
