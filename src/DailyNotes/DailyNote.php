<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\DailyNotes;

use Weph\ObsidianTools\Vault\Note;

final class DailyNote
{
    public function __construct(public readonly \DateTimeImmutable $date, public readonly Note $note)
    {
    }
}
