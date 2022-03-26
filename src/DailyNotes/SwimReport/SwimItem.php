<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\DailyNotes\SwimReport;

use Weph\ObsidianTools\Type\Duration;

final class SwimItem
{
    public function __construct(public readonly string $date, public readonly int $distance, public readonly ?Duration $time)
    {
    }
}
