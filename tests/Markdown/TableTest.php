<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Markdown;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Markdown\Table;

#[CoversClass(Table::class)]
final class TableTest extends TestCase
{
    #[Test]
    public function columns_should_be_stretched_to_an_equal_length(): void
    {
        $table = new Table(['Col A Long', 'Col B', 'Col C']);
        $table->addRow(['Value 1', 'Value 2 Longer', 'Value 3']);
        $table->addRow(['Value 4', 'Value 5', 'Value 6 Longest']);

        self::assertSame(
            "| Col A Long | Col B          | Col C           |\n" .
            "| ---------- | -------------- | --------------- |\n" .
            "| Value 1    | Value 2 Longer | Value 3         |\n" .
            "| Value 4    | Value 5        | Value 6 Longest |\n",
            $table->render()
        );
    }

    #[Test]
    public function column_width_should_be_determined_correctly_when_unicode_characters_are_present(): void
    {
        $table = new Table(['Cöl A', 'Col B']);
        $table->addRow(['Value 1', 'Välue 2']);

        self::assertSame(
            "| Cöl A   | Col B   |\n" .
            "| ------- | ------- |\n" .
            "| Value 1 | Välue 2 |\n",
            $table->render()
        );
    }
}
