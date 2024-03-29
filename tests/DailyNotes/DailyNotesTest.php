<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\DailyNotes;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\DailyNotes\CalendarWeekNotes;
use Weph\ObsidianTools\DailyNotes\DailyNote;
use Weph\ObsidianTools\DailyNotes\DailyNotes;
use Weph\ObsidianTools\Vault\MatchedNote;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\Vault;
use Weph\ObsidianTools\Vault\VaultUsingFilesystem;

#[CoversClass(DailyNotes::class)]
#[UsesClass(CalendarWeekNotes::class)]
#[UsesClass(DailyNote::class)]
#[UsesClass(Note::class)]
#[UsesClass(MatchedNote::class)]
#[UsesClass(VaultUsingFilesystem::class)]
#[UsesClass(Query::class)]
final class DailyNotesTest extends TestCase
{
    private Vault $vault;

    protected function setUp(): void
    {
        parent::setUp();

        $root = vfsStream::setup();

        $this->vault = VaultUsingFilesystem::atPath($root->url());
    }

    #[Test]
    public function it_should_return_daily_notes_per_calendar_week(): void
    {
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-01.md', [], ''));

        $this->vault->save(new Note('Notes/Daily Notes/2022-01-08.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-04.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-05.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-07.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-06.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-09.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-03.md', [], ''));

        $this->vault->save(new Note('Notes/Daily Notes/2022-01-02.md', [], ''));
        $this->vault->save(new Note('Notes/Daily Notes/2022-01-10.md', [], ''));

        $result = (new DailyNotes($this->vault))->calendarWeeks();

        self::assertEquals(
            [
                ['2021-52', ['2022-01-01', '2022-01-02']],
                ['2022-1', ['2022-01-03', '2022-01-04', '2022-01-05', '2022-01-06', '2022-01-07', '2022-01-08', '2022-01-09']],
                ['2022-2', ['2022-01-10']],
            ],
            array_map(
                static fn (CalendarWeekNotes $wn) => [
                    sprintf('%s-%s', $wn->year, $wn->week),
                    array_map(static fn (DailyNote $dn) => $dn->note->name, $wn->dailyNotes),
                ],
                $result
            )
        );
    }

    #[Test]
    public function it_should_return_a_note_by_its_date(): void
    {
        $note = new Note('Daily Notes/2020-01-01.md', [], '');
        $this->vault->save($note);

        $result = (new DailyNotes($this->vault))->get(2020, 1, 1);

        self::assertInstanceOf(DailyNote::class, $result);
        self::assertEquals(new \DateTimeImmutable('2020-01-01'), $result->date);
        self::assertEquals($note, $result->note);
    }

    #[Test]
    public function it_should_return_null_if_there_is_no_note_for_a_date(): void
    {
        $result = (new DailyNotes($this->vault))->get(2020, 1, 1);

        self::assertNull($result);
    }

    #[Test]
    public function it_should_return_a_list_of_all_years_that_have_at_least_one_note(): void
    {
        $this->vault->save(new Note('Daily Notes/2020-01-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-01-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2014-01-01.md', [], ''));

        $result = (new DailyNotes($this->vault))->years();

        self::assertEquals([2014, 2016, 2020], $result);
    }

    #[Test]
    public function it_should_return_a_list_of_all_months_of_a_years_that_have_at_least_one_note(): void
    {
        $this->vault->save(new Note('Daily Notes/2020-01-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-12-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-03-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-01-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2014-01-01.md', [], ''));

        $result = (new DailyNotes($this->vault))->months(2016);

        self::assertEquals([1, 3, 12], $result);
    }

    #[Test]
    public function it_should_return_a_list_of_all_days_of_a_months_years_that_have_a_note(): void
    {
        $this->vault->save(new Note('Daily Notes/2016-12-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-03-22.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-03-10.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-03-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-01-01.md', [], ''));

        $result = (new DailyNotes($this->vault))->days(2016, 3);

        self::assertEquals([1, 10, 22], $result);
    }

    #[Test]
    #[TestWith([2017, 1, '2016-12'])]
    #[TestWith([2016, 12, '2016-11'])]
    #[TestWith([2016, 11, '2015-03'])]
    #[TestWith([2015, 3, null])]
    public function it_should_return_the_previous_month(int $year, int $month, ?string $expected): void
    {
        $this->vault->save(new Note('Daily Notes/2017-01-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-12-31.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-11-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2015-03-01.md', [], ''));

        $result = (new DailyNotes($this->vault))->previousMonth($year, $month);

        self::assertSame($expected, $result);
    }

    #[Test]
    #[TestWith([2017, 1, null])]
    #[TestWith([2016, 12, '2017-01'])]
    #[TestWith([2016, 12, '2017-01'])]
    #[TestWith([2016, 11, '2016-12'])]
    #[TestWith([2015, 3, '2016-11'])]
    public function it_should_return_the_next_month(int $year, int $month, ?string $expected): void
    {
        $this->vault->save(new Note('Daily Notes/2017-01-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-12-31.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-11-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2015-03-01.md', [], ''));

        $result = (new DailyNotes($this->vault))->nextMonth($year, $month);

        self::assertSame($expected, $result);
    }

    #[Test]
    #[TestWith([2017, 1, 1, '2016-12-31'])]
    #[TestWith([2016, 12, 31, '2016-11-02'])]
    #[TestWith([2016, 11, 2, '2016-11-01'])]
    #[TestWith([2016, 11, 1, null])]
    public function it_should_return_the_previous_day(int $year, int $month, int $day, ?string $expected): void
    {
        $this->vault->save(new Note('Daily Notes/2017-01-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-12-31.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-11-02.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-11-01.md', [], ''));

        $result = (new DailyNotes($this->vault))->previousDay($year, $month, $day);

        self::assertSame($expected, $result);
    }

    #[Test]
    #[TestWith([2017, 1, 1, null])]
    #[TestWith([2016, 12, 31, '2017-01-01'])]
    #[TestWith([2016, 11, 2, '2016-12-31'])]
    #[TestWith([2016, 11, 1, '2016-11-02'])]
    public function it_should_return_the_next_day(int $year, int $month, int $day, ?string $expected): void
    {
        $this->vault->save(new Note('Daily Notes/2017-01-01.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-12-31.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-11-02.md', [], ''));
        $this->vault->save(new Note('Daily Notes/2016-11-01.md', [], ''));

        $result = (new DailyNotes($this->vault))->nextDay($year, $month, $day);

        self::assertSame($expected, $result);
    }
}
