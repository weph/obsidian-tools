<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Type;

final class Duration
{
    private function __construct(private readonly int $seconds)
    {
    }

    public static function fromString(string $value): self
    {
        $multiplier = 1;

        $seconds = 0;
        foreach (array_reverse(explode(':', $value)) as $part) {
            $seconds += (int)$part * $multiplier;

            $multiplier *= 60;
        }

        return new self($seconds);
    }

    public static function fromSeconds(int $input): self
    {
        return new self($input);
    }

    public function inSeconds(): int
    {
        return $this->seconds;
    }

    public function asString(): string
    {
        $hours   = floor($this->seconds / 3600);
        $minutes = ($this->seconds % 3600) / 60;
        $seconds = $this->seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
