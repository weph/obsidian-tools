<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Actions;

use Weph\ObsidianTools\DailyNotes\CalendarWeekNotes;
use Weph\ObsidianTools\DailyNotes\DailyNote;
use Weph\ObsidianTools\DailyNotes\DailyNotes;
use Weph\ObsidianTools\Markdown\Table;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Vault;

final class GenerateWeeklySummary implements Action
{
    private DailyNotes $dailyNotes;

    public function __construct(private readonly Vault $vault)
    {
        $this->dailyNotes = new DailyNotes($this->vault);
    }

    public function run(): void
    {
        $weeks = $this->dailyNotes->calendarWeeks();

        foreach ($weeks as $index => $data) {
            $location = sprintf('Notes/Daily Notes/%04d/%s.md', $data->year, $this->noteName($data));

            $content = sprintf("# %s - KW %s\n\n", $data->year, $data->week);
            $content .= $this->habitTracker($data);
            $content .= $this->dailyNotes($data);

            $frontMatter = [];

            if ($index > 0) {
                $frontMatter['prev'] = sprintf('[[%s]]', $this->noteName($weeks[$index - 1]));
            }

            if ($index < count($weeks) - 1) {
                $frontMatter['next'] = sprintf('[[%s]]', $this->noteName($weeks[$index + 1]));
            }

            $dailyNote = new Note($location, $frontMatter, $content);

            $this->vault->save($dailyNote);
        }
    }

    private function habitTracker(CalendarWeekNotes $calendarWeekNotes): string
    {
        $tags = [];
        foreach ($calendarWeekNotes->dailyNotes as $dailyNote) {
            if (!preg_match_all('/(#[a-z0-9\/]+)/', $dailyNote->note->content, $matches)) {
                continue;
            }

            foreach ($matches[1] as $tag) {
                if (!isset($tags[$tag])) {
                    $tags[$tag] = array_fill(0, 7, '');
                }
            }

            $day = ((int)($dailyNote->date)->format('N')) - 1;
            foreach ($matches[1] as $tag) {
                $tags[$tag][$day] .= '✓';
            }
        }

        if (count($tags) === 0) {
            return '';
        }

        $table = new Table(['', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']);
        foreach ($tags as $tag => $days) {
            $table->addRow([$tag, ...$days]);
        }

        return sprintf("## Habits\n\n%s\n\n", $table->render());
    }

    private function dailyNotes(CalendarWeekNotes $calendarWeekNotes): string
    {
        return sprintf(
            "## Notes\n\n%s\n\n",
            implode("\n", array_map(static fn (DailyNote $v) => sprintf('![[%s]]', $v->note->name), $calendarWeekNotes->dailyNotes))
        );
    }

    private function noteName(CalendarWeekNotes $calendarWeekNotes): string
    {
        return sprintf('%04d-W%02d', $calendarWeekNotes->year, $calendarWeekNotes->week);
    }
}
