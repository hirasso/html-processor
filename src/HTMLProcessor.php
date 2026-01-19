<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use Asika\Autolink\AutolinkOptions;
use Closure;
use Hirasso\HTMLProcessor\Queue\DOMQueue;
use Hirasso\HTMLProcessor\Queue\HTMLQueue;
use Hirasso\HTMLProcessor\Service\DOM\EmptyElements;
use Hirasso\HTMLProcessor\Service\DOM\LinkProcessor\Link;
use Hirasso\HTMLProcessor\Service\DOM\LinkProcessor\LinkProcessor;
use Hirasso\HTMLProcessor\Service\DOM\PrefixLinker;
use Hirasso\HTMLProcessor\Service\HTML\EmailEncoder;
use Hirasso\HTMLProcessor\Service\DOM\Typography\Typography;
use Hirasso\HTMLProcessor\Service\DOM\Autolinker;
use Hirasso\HTMLProcessor\Support\Support;

/**
 * Process a HTML string using a fluent API
 * @see https://github.com/hirasso/html-processor
 */
final class HTMLProcessor
{
    /** track if entities should be decoded */
    public bool $preserveEntities = false;

    /** used for typography optimizations */
    protected string $locale = 'en_US';

    protected DOMQueue $domQueueEarly;
    protected DOMQueue $domQueue;
    protected HTMLQueue $htmlQueue;

    protected bool $mutated = false;

    protected function __construct(
        protected readonly string $originalHTML
    ) {
        $this->domQueueEarly = new DOMQueue();
        $this->domQueue = new DOMQueue();
        $this->htmlQueue = new HTMLQueue();
    }

    /**
     * Create an instance from a string of HTML
     */
    public static function fromString(string $html): self
    {
        return new self($html);
    }

    /**
     * Mutate, return self
     * @param Closure(): mixed $mutation
     */
    protected function mutate(Closure $mutation): self
    {
        $mutation();
        $this->mutated = true;
        return $this;
    }

    /**
     * Make urls clickable
     */
    public function autolinkUrls(?AutolinkOptions $options = null): self
    {
        return $this->mutate(function () use ($options) {
            $this->domQueueEarly->add(new Autolinker($options  ?? new AutolinkOptions(
                stripScheme: true,
                textLimit: 35,
                autoTitle: false,
                escape: true,
                linkNoScheme: true
            )));
        });
    }

    /**
     * Automatically link @foobar or #hashtag to a social network (or anywhere)
     */
    public function autolinkPrefix(string $prefix, string $url): self
    {
        return $this->mutate(function () use ($prefix, $url) {
            $linker = $this->domQueue->get(PrefixLinker::class)
            ?? new PrefixLinker();

            $linker->register($prefix, $url);

            $this->domQueue->add($linker);
        });
    }

    /**
     * Add classes to links, open external links in a new tab, etc.
     *
     * @param ?Closure(Link $link, Closure(string=): mixed $defaultHandler): mixed $callback
     */
    public function processLinks(?Closure $callback = null): self
    {
        return $this->mutate(function () use ($callback) {
            $this->domQueue->add(new LinkProcessor($callback));
        });
    }

    /**
     * Remove empty elements
     */
    public function removeEmptyElements(?string $selector = null): self
    {
        return $this->mutate(function () use ($selector) {
            $this->domQueue->add(new EmptyElements($selector));
        });
    }

    /**
     * Optimize typography
     *
     * @param string $locale – e.g. 'en_EN', 'de_DE' or even 'de_DE_formal' Only the first bit will be used
     * @param null|Closure(Typography): mixed $callback – customize via callback
     */
    public function typography(
        string $locale,
        ?Closure $callback = null,
    ): self {
        return $this->mutate(function () use ($locale, $callback) {
            $instance = Typography::fromLocale($locale);
            $this->domQueue->add($instance);

            /** Apply the callback */
            if ($callback instanceof Closure) {
                ($callback)($instance);
                return $this;
            }

            $this->domQueue->add($instance);
        });
    }

    /**
     * Encode Email addresses to protect them from spam bots
     */
    public function encodeEmails(): self
    {
        return $this->mutate(function () {
            $this->preserveEntities();
            $this->htmlQueue->add(new EmailEncoder());
        });
    }

    /**
     * Execute all queued operations in optimal order
     *
     * @return string – the processed HTML string
     */
    public function apply(): string
    {
        if (empty($this->originalHTML) || !$this->mutated) {
            return $this->originalHTML;
        }

        $html = $this->originalHTML;
        $html = $this->domQueueEarly->applyTo($html);
        $html = $this->domQueue->applyTo($html);
        $html = $this->htmlQueue->applyTo($html);

        if (!$this->preserveEntities) {
            return Support::decode($html);
        }

        // When preserving entities, only decode htmlspecialchars (&lt; &gt; &amp; &quot;)
        // while keeping numeric entities (&#109; &#x6d; &nbsp; etc.)
        return htmlspecialchars_decode($html);
    }

    /**
     * Magic method to directly echo the document
     */
    public function __toString(): string
    {
        return $this->apply();
    }

    /**
     * Preserve entities explicitly
     */
    public function preserveEntities(): self
    {
        $this->preserveEntities = true;
        return $this;
    }
}
