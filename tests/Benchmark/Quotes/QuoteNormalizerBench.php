<?php

declare(strict_types=1);

namespace Tests\Benchmark\Quotes;

use Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer\QuoteNormalizer;
use Hirasso\HTMLProcessor\Support\Support;
use PhpBench\Attributes as Bench;

#[Bench\BeforeMethods('setUp')]
class QuoteNormalizerBench
{
    private QuoteNormalizer $normalizer;
    private string $simple;
    private string $nested;
    private string $dense;
    private string $long;

    public function setUp(): void
    {
        $this->normalizer = new QuoteNormalizer();

        $this->simple = BenchmarkFixtures::SIMPLE;
        $this->nested = BenchmarkFixtures::NESTED;
        $this->dense = BenchmarkFixtures::dense();
        $this->long = BenchmarkFixtures::long();
    }

    #[Bench\Revs(1000)]
    #[Bench\Iterations(5)]
    public function benchSimple(): void
    {
        $doc = Support::createDocument($this->simple);
        $this->normalizer->run($doc);
    }

    #[Bench\Revs(1000)]
    #[Bench\Iterations(5)]
    public function benchNested(): void
    {
        $doc = Support::createDocument($this->nested);
        $this->normalizer->run($doc);
    }

    #[Bench\Revs(1000)]
    #[Bench\Iterations(5)]
    public function benchDense(): void
    {
        $doc = Support::createDocument($this->dense);
        $this->normalizer->run($doc);
    }

    #[Bench\Revs(500)]
    #[Bench\Iterations(5)]
    public function benchLong(): void
    {
        $doc = Support::createDocument($this->long);
        $this->normalizer->run($doc);
    }
}
