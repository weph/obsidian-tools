<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\DailyNotes;

use Weph\ObsidianTools\Vault\Note;
use Weph\ObsidianTools\Vault\Query;
use Weph\ObsidianTools\Vault\Vault;

final class DailyNotes
{
    /**
     * @var array<string, Note>
     */
    private array $notes = [];

    /**
     * @var list<int>
     */
    private array $years = [];

    /**
     * @var list<string>
     */
    private array $months = [];

    public function __construct(private readonly Vault $vault)
    {
        $query = Query::create()
            ->withLocation('|Daily Notes|')
            ->withFilename('|\d{4}-\d{2}-\d{2}.md|');

        $matches = $this->vault->notesMatching($query);

        $notes  = [];
        $months = [];
        $years  = [];
        foreach ($matches as $match) {
            $date = str_replace('.md', '', basename($match->note->path));

            [$year, $month,] = explode('-', $date);

            $notes[$date] = $match->note;

            $month             = sprintf('%s-%s', $year, $month);
            $months[$month]    = 1;
            $years[(int)$year] = 1;
        }

        ksort($notes);
        ksort($months);
        ksort($years);

        $this->notes  = $notes;
        $this->months = array_keys($months);
        $this->years  = array_keys($years);
    }

    /**
     * @return list<Note>
     */
    public function all(): array
    {
        return array_values($this->notes);
    }

    /**
     * @return list<int>
     */
    public function years(): array
    {
        return $this->years;
    }

    /**
     * @return list<int>
     */
    public function months(int $year): array
    {
        return array_values(
            array_map(
                static fn (string $date) => (int)preg_replace('/\d{4}-(\d{2})/', '$1', $date),
                array_filter(
                    $this->months,
                    static fn (string $month) => str_starts_with($month, (string)$year)
                )
            )
        );
    }

    /**
     * @return list<int>
     */
    public function days(int $year, int $month): array
    {
        return array_values(
            array_map(
                static fn (string $date) => (int)preg_replace('/\d{4}-\d{2}-(\d{2})/', '$1', $date),
                array_filter(
                    array_keys($this->notes),
                    static fn (string $v) => str_starts_with($v, sprintf('%04d-%02d', $year, $month))
                )
            )
        );
    }

    public function previousMonth(int $year, int $month): ?string
    {
        $date = sprintf('%04d-%02d', $year, $month);

        $pos = array_search($date, $this->months);
        if ($pos === false || $pos === 0) {
            return null;
        }

        return $this->months[$pos - 1];
    }

    public function nextMonth(int $year, int $month): ?string
    {
        $date = sprintf('%04d-%02d', $year, $month);

        $pos = array_search($date, $this->months);
        if ($pos === false || $pos >= count($this->months) - 1) {
            return null;
        }

        return $this->months[$pos + 1];
    }

    public function previousDay(int $year, int $month, int $day): ?string
    {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $days = array_keys($this->notes);

        $pos = array_search($date, $days);
        if ($pos === false || $pos === 0) {
            return null;
        }

        return $days[$pos - 1];
    }

    public function nextDay(int $year, int $month, int $day): ?string
    {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $days = array_keys($this->notes);

        $pos = array_search($date, $days);
        if ($pos === false || $pos >= count($days) - 1) {
            return null;
        }

        return $days[$pos + 1];
    }
}
