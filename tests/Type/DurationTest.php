<?php
declare(strict_types=1);

namespace Tests\Weph\ObsidianTools\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Weph\ObsidianTools\Type\Duration;

#[CoversClass(Duration::class)]
final class DurationTest extends TestCase
{
    #[Test]
    #[DataProvider('validStrings')]
    public function create_from_string(string $input): void
    {
        self::assertEquals($input, Duration::fromString($input)->asString());
    }

    /**
     * @return iterable<array-key, array{0: string}>
     */
    public static function validStrings(): iterable
    {
        yield ['00:00'];
        yield ['00:01'];
        yield ['01:00'];
        yield ['59:00'];
        yield ['01:00:00'];
    }

    #[Test]
    #[DataProvider('validSeconds')]
    public function create_from_int(int $input, string $expected): void
    {
        self::assertEquals($expected, Duration::fromSeconds($input)->asString());
    }

    /**
     * @return iterable<array-key, array{0: int, 1: string}>
     */
    public static function validSeconds(): iterable
    {
        yield [0, '00:00'];
        yield [1, '00:01'];
        yield [60, '01:00'];
        yield [3540, '59:00'];
        yield [3600, '01:00:00'];
    }
}
