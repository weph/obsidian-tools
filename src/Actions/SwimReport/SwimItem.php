<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Actions\SwimReport;

use Weph\ObsidianTools\Type\Duration;

final class SwimItem
{
    public function __construct(public readonly string $date, public readonly int $distance, public readonly ?Duration $time)
    {
    }

    public function averageTime(): ?Duration
    {
        if ($this->time === null) {
            return null;
        }

        return Duration::fromSeconds((int)round($this->time->inSeconds() / $this->distance * 100));
    }
}
