<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Actions\WeeklySummary;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Actions\WeeklySummary\HabitTracker;
use Weph\ObsidianTools\DailyNotes\CalendarWeekNotes;
use Weph\ObsidianTools\DailyNotes\DailyNote;
use Weph\ObsidianTools\Markdown\Table;
use Weph\ObsidianTools\Vault\Note;

#[CoversClass(HabitTracker::class)]
#[UsesClass(Table::class)]
#[UsesClass(CalendarWeekNotes::class)]
#[UsesClass(DailyNote::class)]
#[UsesClass(Note::class)]
final class HabitTrackerTest extends TestCase
{
    #[Test]
    public function it_should_render_an_empty_table_if_nothing_was_tracked(): void
    {
        $calendarWeekNotes = new CalendarWeekNotes(2022, 18, []);

        $result = (new HabitTracker(['track/testing' => 'Testing', 'track/coding' => 'Coding']))->render($calendarWeekNotes);

        self::assertEquals(
            "|         | Mo | Di | Mi | Do | Fr | Sa | So |\n" .
            "| ------- | -- | -- | -- | -- | -- | -- | -- |\n" .
            "| Testing |    |    |    |    |    |    |    |\n" .
            "| Coding  |    |    |    |    |    |    |    |\n",
            $result
        );
    }

    #[Test]
    public function it_should_ignore_other_tags(): void
    {
        $calendarWeekNotes = new CalendarWeekNotes(2022, 18, [
            new DailyNote(new \DateTimeImmutable('2022-05-02'), new Note('day1.md', [], '- #habit')),
            new DailyNote(new \DateTimeImmutable('2022-05-03'), new Note('day2.md', [], '- #somethingelse')),
            new DailyNote(new \DateTimeImmutable('2022-05-04'), new Note('day3.md', [], '- #habit #somethingelse #habit')),
        ]);

        $result = (new HabitTracker(['habit' => 'Habit']))->render($calendarWeekNotes);

        self::assertEquals(
            "|       | Mo | Di | Mi | Do | Fr | Sa | So |\n" .
            "| ----- | -- | -- | -- | -- | -- | -- | -- |\n" .
            "| Habit | ✓  |    | ✓✓ |    |    |    |    |\n",
            $result
        );
    }

    #[Test]
    public function it_should_render_a_checkmark_for_each_occurrence_of_a_tag(): void
    {
        $calendarWeekNotes = new CalendarWeekNotes(2022, 18, [
            new DailyNote(new \DateTimeImmutable('2022-05-02'), new Note('day1.md', [], '- #a')),
            new DailyNote(new \DateTimeImmutable('2022-05-03'), new Note('day2.md', [], '- #b')),
            new DailyNote(new \DateTimeImmutable('2022-05-04'), new Note('day3.md', [], '- #c')),
            new DailyNote(new \DateTimeImmutable('2022-05-05'), new Note('day4.md', [], '- #d')),
            new DailyNote(new \DateTimeImmutable('2022-05-06'), new Note('day5.md', [], '')),
            new DailyNote(new \DateTimeImmutable('2022-05-07'), new Note('day6.md', [], '#a #b #b #c #c #c')),
            new DailyNote(new \DateTimeImmutable('2022-05-08'), new Note('day7.md', [], '#a #b #c #d #e #f')),
        ]);

        $result = (new HabitTracker(['a' => 'Habit A', 'b' => 'Habit B', 'c' => 'Habit C', 'd' => 'Habit D', 'e' => 'Habit E', 'f' => 'Habit F', 'g' => 'Habit G']))->render($calendarWeekNotes);

        self::assertStringContainsString(
            "|         | Mo | Di | Mi | Do | Fr | Sa  | So |\n" .
            "| ------- | -- | -- | -- | -- | -- | --- | -- |\n" .
            "| Habit A | ✓  |    |    |    |    | ✓   | ✓  |\n" .
            "| Habit B |    | ✓  |    |    |    | ✓✓  | ✓  |\n" .
            "| Habit C |    |    | ✓  |    |    | ✓✓✓ | ✓  |\n" .
            "| Habit D |    |    |    | ✓  |    |     | ✓  |\n" .
            "| Habit E |    |    |    |    |    |     | ✓  |\n" .
            "| Habit F |    |    |    |    |    |     | ✓  |\n",
            $result
        );
    }
}
