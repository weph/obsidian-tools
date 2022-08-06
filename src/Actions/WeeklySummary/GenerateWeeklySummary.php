<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Actions\WeeklySummary;

use Weph\ObsidianTools\Actions\Action;
use Weph\ObsidianTools\DailyNotes\CalendarWeekNotes;
use Weph\ObsidianTools\DailyNotes\DailyNote;
use Weph\ObsidianTools\DailyNotes\DailyNotes;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Vault;

final class GenerateWeeklySummary implements Action
{
    private DailyNotes $dailyNotes;

    private HabitTracker $habitTracker;

    /**
     * @param array{tagLabels?: array<string, string>} $options
     */
    public function __construct(private readonly Vault $vault, array $options)
    {
        $this->dailyNotes   = new DailyNotes($this->vault);
        $this->habitTracker = new HabitTracker($options['tagLabels'] ?? []);
    }

    public function run(): void
    {
        $weeks = $this->dailyNotes->calendarWeeks();

        foreach ($weeks as $index => $data) {
            $location = sprintf('Notes/Daily Notes/%04d/%s.md', $data->year, $this->noteName($data));

            $content = sprintf("# %s - KW %s\n\n", $data->year, $data->week);

            $habitTracker = $this->habitTracker->render($data);
            if ($habitTracker !== '') {
                $content .= "## Habits\n\n";
                $content .= $habitTracker;
            }

            $content .= $this->dailyNotes($data);

            $frontMatter = ['parent' => '[[Journal]]'];

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
