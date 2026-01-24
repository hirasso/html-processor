<?php

declare(strict_types=1);

namespace Tests\Benchmark\Quotes;

use Hirasso\HTMLProcessor\Service\DOM\Quotes\QuoteNormalizer;
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

        $this->simple = '<p>' . BenchmarkFixtures::SIMPLE . '</p>';
        $this->nested = '<p>' . BenchmarkFixtures::NESTED . '</p>';
        $this->dense = '<p>' . BenchmarkFixtures::dense() . '</p>';
        $this->long = '<p>' . BenchmarkFixtures::long() . '</p>';
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
