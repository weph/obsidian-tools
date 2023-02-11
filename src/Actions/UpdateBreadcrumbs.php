<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Actions;

use Weph\ObsidianTools\DailyNotes\DailyNotes;
use Weph\ObsidianTools\Vault\Vault;

final class UpdateBreadcrumbs implements Action
{
    private readonly DailyNotes $dailyNotes;

    public function __construct(private readonly Vault $vault)
    {
        $this->dailyNotes = new DailyNotes($this->vault);
    }

    public function run(): void
    {
        foreach ($this->dailyNotes->all() as $dailyNote) {
            $year  = $dailyNote->date->format('Y');
            $month = $dailyNote->date->format('m');
            $day   = $dailyNote->date->format('d');

            $note = $dailyNote->note;

            $parents = [
                $dailyNote->date->format('o-\WW'),
                $dailyNote->date->format('Y-m'),
            ];

            $note = $note->withFrontMatterField('parent', array_map(static fn (string $s) => sprintf('[[%s]]', $s), $parents));

            $prev = $this->dailyNotes->previousDay((int)$year, (int)$month, (int)$day);
            if ($prev !== null) {
                $note = $note->withFrontMatterField('prev', sprintf('[[%s]]', $prev));
            }

            $next = $this->dailyNotes->nextDay((int)$year, (int)$month, (int)$day);
            if ($next !== null) {
                $note = $note->withFrontMatterField('next', sprintf('[[%s]]', $next));
            }

            $this->vault->save($note);
        }
    }
}
