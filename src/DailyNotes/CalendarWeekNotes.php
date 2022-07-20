<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\DailyNotes;

final class CalendarWeekNotes
{
    /**
     * @param list<DailyNote> $dailyNotes
     */
    public function __construct(public readonly int $year, public readonly int $week, public readonly array $dailyNotes)
    {
    }
}
