<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Actions\WeeklySummary;

use Weph\ObsidianTools\DailyNotes\CalendarWeekNotes;
use Weph\ObsidianTools\Markdown\Table;

final class HabitTracker
{
    /**
     * @param array<string, string> $tagLabels
     */
    public function __construct(private readonly array $tagLabels)
    {
    }

    public function render(CalendarWeekNotes $calendarWeekNotes): string
    {
        $tags = array_fill_keys(array_keys($this->tagLabels), array_fill(0, 7, ''));
        foreach ($calendarWeekNotes->dailyNotes as $dailyNote) {
            if (!preg_match_all('/#([a-z0-9\/]+)/', $dailyNote->note->content, $matches)) {
                continue;
            }

            $day = ((int)$dailyNote->date->format('N')) - 1;
            foreach ($matches[1] as $tag) {
                if (!isset($tags[$tag])) {
                    continue;
                }

                $tags[$tag][$day] .= '✓';
            }
        }

        if (count($tags) === 0) {
            return '';
        }

        $table = new Table(['', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']);
        foreach ($tags as $tag => $values) {
            $table->addRow([$this->tagLabels[$tag], ...$values]);
        }

        return $table->render();
    }
}
