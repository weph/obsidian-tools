<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Actions;

use Weph\ObsidianTools\DailyNotes\DailyNotes;
use Weph\ObsidianTools\Vault\Vault;

final class UpdateBreadcrumbs implements Action
{
    private DailyNotes $dailyNotes;

    public function __construct(private readonly Vault $vault)
    {
        $this->dailyNotes = new DailyNotes($this->vault);
    }

    public function run(): void
    {
        foreach ($this->dailyNotes->all() as $note) {
            [$year, $month, $day] = explode('-', $note->name, 3);

            $note = $note->withFrontMatterField('parent', sprintf('[[%s-%s]]', $year, $month));

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
