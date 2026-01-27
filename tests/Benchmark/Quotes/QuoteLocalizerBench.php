<?php

declare(strict_types=1);

namespace Tests\Benchmark\Quotes;

use Hirasso\HTMLProcessor\Service\DOM\QuoteLocalizer\QuotePair;
use Hirasso\HTMLProcessor\Service\DOM\QuoteLocalizer\QuoteReplacer;
use PhpBench\Attributes as Bench;

#[Bench\BeforeMethods('setUp')]
class QuoteLocalizerBench
{
    private QuoteReplacer $replacer;
    private string $simple;
    private string $nested;
    private string $dense;
    private string $long;

    public function setUp(): void
    {
        $this->replacer = new QuoteReplacer(
            lang: 'de',
            single: new QuotePair("\u{201A}", "\u{2018}"),
            double: new QuotePair("\u{201E}", "\u{201C}"),
        );

        $this->simple = BenchmarkFixtures::SIMPLE;
        $this->nested = BenchmarkFixtures::NESTED;
        $this->dense = BenchmarkFixtures::dense();
        $this->long = BenchmarkFixtures::long();
    }

    #[Bench\Revs(1000)]
    #[Bench\Iterations(5)]
    public function benchSimple(): void
    {
        $this->replacer->applyTo($this->simple);
    }

    #[Bench\Revs(1000)]
    #[Bench\Iterations(5)]
    public function benchNested(): void
    {
        $this->replacer->applyTo($this->nested);
    }

    #[Bench\Revs(1000)]
    #[Bench\Iterations(5)]
    public function benchDense(): void
    {
        $this->replacer->applyTo($this->dense);
    }

    #[Bench\Revs(500)]
    #[Bench\Iterations(5)]
    public function benchLong(): void
    {
        $this->replacer->applyTo($this->long);
    }
}
