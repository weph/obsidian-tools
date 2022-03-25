<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\DailyNotes\SwimReport;

final class SwimItems
{
    /**
     * @var list<SwimItem>
     */
    private array $items;

    /**
     * @param list<SwimItem> $items
     */
    private function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function add(string $date, int $distance, ?int $time): void
    {
        $this->items[] = new SwimItem($date, $distance, $time);
    }

    /**
     * @return list<int>
     */
    public function years(): array
    {
        return array_values(array_unique(array_map(static fn (SwimItem $v) => (int)substr($v->date, 0, 4), $this->items)));
    }

    public function sortByDate(): void
    {
        usort($this->items, static fn (SwimItem $a, SwimItem $b) => $a->date <=> $b->date);
    }

    public function totalDistance(): int
    {
        return array_sum(array_map(static fn (SwimItem $v) => $v->distance, $this->items));
    }

    public function filterDate(string $filter): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->items,
                    static fn (SwimItem $item) => str_starts_with($item->date, $filter)
                )
            )
        );
    }

    /**
     * @return list<SwimItem>
     */
    public function items(): array
    {
        return $this->items;
    }
}
