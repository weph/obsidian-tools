<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Actions;

use DateTimeImmutable;
use IntlDateFormatter;
use Weph\ObsidianTools\DailyNotes\DailyNotes;
use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Vault;

final class GenerateJournalSummaries implements Action
{
    private DailyNotes $dailyNotes;

    public function __construct(private readonly Vault $vault)
    {
        $this->dailyNotes = new DailyNotes($this->vault);
    }

    public function run(): void
    {
        $this->createJournalSummary();

        foreach ($this->dailyNotes->years() as $year) {
            foreach ($this->dailyNotes->months($year) as $month) {
                $this->createMonthlySummary($year, $month);
            }
        }
    }

    private function createJournalSummary(): void
    {
        $formatter = new IntlDateFormatter('de_DE.UTF-8', IntlDateFormatter::NONE, IntlDateFormatter::NONE);
        $formatter->setPattern('MMMM');

        $years = $this->dailyNotes->years();

        $content = "# Journal\n\n";

        foreach (array_reverse($years) as $year) {
            $content .= sprintf("## %s\n", $year);
            $content .= implode(
                " / \n",
                array_map(
                    static fn (int $month) => sprintf(
                        '[[%04d-%02d|%s]]',
                        $year,
                        $month,
                        $formatter->format(DateTimeImmutable::createFromFormat('Ymd', sprintf('%04d%02d01', $year, $month)))
                    ),
                    $this->dailyNotes->months($year)
                )
            );
            $content .= "\n\n";
        }

        $note = new Note('Notes/Daily Notes/Journal.md', ['parent' => '[[Index]]', 'tags' => ['generated']], $content);

        $this->vault->save($note);
    }

    private function createMonthlySummary(int $year, int $month): void
    {
        $formatter = new IntlDateFormatter('de_DE.UTF-8', IntlDateFormatter::NONE, IntlDateFormatter::NONE);
        $formatter->setPattern('MMMM yyyy');
        $title = $formatter->format(DateTimeImmutable::createFromFormat('Ymd', sprintf('%04d%02d01', $year, $month)));

        $frontMatter = [
            'parent' => '[[Journal]]',
            'tags'   => ['generated', sprintf('Journal/%04d/%02d', $year, $month)],
        ];

        $prevMonth = $this->dailyNotes->previousMonth($year, $month);
        if ($prevMonth !== null) {
            $frontMatter['prev'] = sprintf('[[%s]]', $prevMonth);
        }
        $nextMonth = $this->dailyNotes->nextMonth($year, $month);
        if ($nextMonth !== null) {
            $frontMatter['next'] = sprintf('[[%s]]', $nextMonth);
        }

        $location = sprintf('Notes/Daily Notes/%04d/%02d/%04d-%02d.md', $year, $month, $year, $month);
        $content  = sprintf("# %s\n\n", $title);
        $content .= sprintf("%s\n\n", implode("\n", array_map(static fn (int $day) => sprintf('![[%04d-%02d-%02d]]', $year, $month, $day), $this->dailyNotes->days($year, $month))));

        $note = new Note($location, $frontMatter, $content);

        $this->vault->save($note);
    }
}
