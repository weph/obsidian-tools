<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Actions;

use DateTimeImmutable;
use Weph\ObsidianTools\DailyNotes\DailyNotes;
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
        $weeks = [];

        foreach ($this->dailyNotes->years() as $year) {
            foreach ($this->dailyNotes->months($year) as $month) {
                foreach ($this->dailyNotes->days($year, $month) as $day) {
                    $date = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));

                    $week = $date->format('o-\WW');

                    if (!isset($weeks[$week])) {
                        $weeks[$week] = [
                            'year'  => $date->format('o'),
                            'week'  => $date->format('W'),
                            'notes' => [],
                        ];
                    }

                    $weeks[$week]['notes'][] = $date->format('Y-m-d');
                }
            }
        }

        foreach ($weeks as $current => $data) {
            $location = sprintf('Notes/Daily Notes/%04d/%s-W%s.md', $data['year'], $data['year'], $data['week']);
            $content  = sprintf("# %s - KW %s\n\n", $data['year'], (int)$data['week']);
            $content .= sprintf("%s\n\n", implode("\n", array_map(static fn (string $v) => sprintf('![[%s]]', $v), $data['notes'])));

            $frontMatter  = [];
            $previousWeek = $this->previousWeek($weeks, $current);
            $nextWeek     = $this->nextWeek($weeks, $current);

            if ($previousWeek !== null) {
                $frontMatter['prev'] = sprintf('[[%s]]', $previousWeek);
            }

            if ($nextWeek !== null) {
                $frontMatter['next'] = sprintf('[[%s]]', $nextWeek);
            }

            $note = new Note($location, $frontMatter, $content);

            $this->vault->save($note);
        }
    }

    /**
     * @param array<string, mixed> $weeks
     */
    private function previousWeek(array $weeks, string $current): ?string
    {
        $keys  = array_keys($weeks);
        $index = array_search($current, $keys);

        if ($index === false) {
            return null;
        }

        return $keys[$index - 1] ?? null;
    }

    /**
     * @param array<string, mixed> $weeks
     */
    private function nextWeek(array $weeks, string $current): ?string
    {
        $keys  = array_keys($weeks);
        $index = array_search($current, $keys);

        if ($index === false) {
            return null;
        }

        return $keys[$index + 1] ?? null;
    }
}
