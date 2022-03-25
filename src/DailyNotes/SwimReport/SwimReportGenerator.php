<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\DailyNotes\SwimReport;

use DateTimeImmutable;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\Vault;

final class SwimReportGenerator
{
    private Vault $vault;

    public function __construct(Vault $vault)
    {
        $this->vault = $vault;
    }

    public function run(): void
    {
        $items = $this->swimItems();

        $years = $items->years();

        for ($i = 0; $i < count($years); ++$i) {
            $year = $years[$i];

            $frontMatter = [
                'parent' => '[[Schwimmen]]',
                'tags'   => ['generated'],
            ];

            $prev = $years[$i - 1] ?? null;
            if ($prev !== null) {
                $frontMatter['prev'] = sprintf('[[Schwimmen (%s)]]', $prev);
            }

            $next = $years[$i + 1] ?? null;
            if ($next !== null) {
                $frontMatter['next'] = sprintf('[[Schwimmen (%s)]]', $next);
            }

            $this->vault->save(
                new Note(
                    sprintf('UNSORTED/Schwimmen (%s).md', $year),
                    $frontMatter,
                    $this->contentForYear($year, $items->filterDate((string)$year))
                )
            );
        }

        $content = "# Schwimmen\n\n";

        foreach ($years as $year) {
            $content .= sprintf("- [[Schwimmen (%s)|%s]]: %s\n", $year, $year, number_format($items->filterDate((string)$year)->totalDistance()));
        }

        $this->vault->save(new Note('UNSORTED/Schwimmen.md', ['tags' => ['generated']], $content));
    }

    private function durationToSeconds(string $duration): int
    {
        [$minutes, $seconds] = explode(':', $duration);

        return (int)$minutes * 60 + (int)$seconds;
    }

    private function secondsAsDuration(int $seconds): string
    {
        return sprintf('%02d:%02d', $seconds / 60, $seconds % 60);
    }

    private function swimItems(): SwimItems
    {
        $query = new Query('/Schwimmen::? (?P<distance>.+) Meter(?: in (?P<time>\d{2}:\d{2}))?/');

        $items = SwimItems::empty();
        foreach ($this->vault->notesMatching($query) as $match) {
            $date = str_replace('.md', '', basename($match->note->path));

            foreach ($match->matches as $item) {
                $items->add(
                    $date,
                    (int)str_replace('.', '', $item['distance']),
                    isset($item['time']) && $item['time'] !== '' ? $this->durationToSeconds($item['time']) : null
                );
            }
        }

        $items->sortByDate();

        return $items;
    }

    private function table(SwimItems $items): string
    {
        $lines = [
            sprintf('| %-10s | %8s | %6s |', 'Datum', 'Distanz', 'Zeit'),
            sprintf('|:%s|%s:|%s:|', str_repeat('-', 11), str_repeat('-', 9), str_repeat('-', 7)),
        ];

        foreach ($items->items() as $item) {
            $lines[] = sprintf('| %s | %8s | %6s |', $item->date, number_format($item->distance, 0, ',', '.'), $item->time === null ? '' : $this->secondsAsDuration($item->time));
        }

        return implode("\n", $lines);
    }

    private function contentForYear(int $year, SwimItems $items): string
    {
        $content = sprintf("# %s\n\n", $year);
        $content .= sprintf("Insgesamt: %s\n\n", number_format($items->totalDistance()));

        for ($month = 1; $month <= 12; ++$month) {
            $date = DateTimeImmutable::createFromFormat('n', (string)$month);
            assert($date instanceof DateTimeImmutable);
            $monthName = $date->format('F');

            $content .= sprintf("- %s: %s\n", $monthName, number_format($items->filterDate(sprintf('%s-%02d', $year, $month))->totalDistance(), 0, ',', '.'));
        }

        $content .= "\n";
        $content .= $this->table($items);
        $content .= "\n";

        return $content;
    }
}
