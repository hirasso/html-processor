<?php

declare(strict_types=1);

namespace Tests\Benchmark\Quotes;

final class BenchmarkFixtures
{
    public const SIMPLE = 'This is "a simple test" with \'some quotes\' in it.';
    public const NESTED = '"outer "inner \'deep\' inner" outer" and more "quotes" here.';

    public static function dense(): string
    {
        return str_repeat('"quoted" ', 100);
    }

    public static function long(): string
    {
        return str_repeat('Some text with "quotes" and \'apostrophes\' mixed in. ', 50);
    }
}
