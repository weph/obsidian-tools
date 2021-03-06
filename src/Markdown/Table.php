<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Markdown;

final class Table
{
    /**
     * @var non-empty-list<int>
     */
    private array $columnWidths;

    /**
     * @var list<non-empty-list<string>>
     */
    private array $rows = [];

    /**
     * @param non-empty-list<string> $header
     */
    public function __construct(private readonly array $header)
    {
        $this->columnWidths = array_map('\mb_strlen', $this->header);
    }

    /**
     * @param non-empty-list<string> $values
     */
    public function addRow(array $values): void
    {
        foreach ($values as $i => $value) {
            if (mb_strlen($value) > $this->columnWidths[$i]) {
                $this->columnWidths[$i] = mb_strlen($value);
            }
        }

        $this->rows[] = $values;
    }

    public function render(): string
    {
        $rows = [
            $this->renderRow($this->header),
            $this->renderRow(array_map(static fn (int $v) => str_repeat('-', $v), $this->columnWidths)),
        ];

        foreach ($this->rows as $row) {
            $rows[] = $this->renderRow($row);
        }

        return implode("\n", $rows) . "\n";
    }

    /**
     * @param non-empty-list<string> $row
     */
    private function renderRow(array $row): string
    {
        $paddedCells = [];
        foreach ($row as $index => $value) {
            $paddedCells[] = $value . str_repeat(' ', $this->columnWidths[$index] - mb_strlen($value));
        }

        return sprintf('| %s |', implode(' | ', $paddedCells));
    }
}
