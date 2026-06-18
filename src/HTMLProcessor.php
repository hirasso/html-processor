<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use Asika\Autolink\AutolinkOptions;
use Closure;
use Hirasso\HTMLProcessor\Exceptions\DumpAndDieException;
use Hirasso\HTMLProcessor\Queue\DOMQueue;
use Hirasso\HTMLProcessor\Queue\HTMLQueue;
use Hirasso\HTMLProcessor\Service\DOM\EmptyElements;
use Hirasso\HTMLProcessor\Service\DOM\LinkProcessor\Link;
use Hirasso\HTMLProcessor\Service\DOM\LinkProcessor\LinkProcessor;
use Hirasso\HTMLProcessor\Service\DOM\PrefixLinker;
use Hirasso\HTMLProcessor\Service\HTML\ObfuscateContacts;
use Hirasso\HTMLProcessor\Service\DOM\Autolinker;
use Hirasso\HTMLProcessor\Service\HTML\StripTags;

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

    protected DOMQueue $domQueue;
    protected HTMLQueue $htmlQueue;

    protected bool $mutated = false;

    protected function __construct(
        protected readonly string $originalHTML
    ) {
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
            $this->domQueue->add(new Autolinker($options  ?? new AutolinkOptions(
                stripScheme: true,
                textLimit: 35,
                autoTitle: false,
                escape: true,
                // poses issues with e.g. "Architekt.innen"
                linkNoScheme: false
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
     * @param string|list<string>|null $allowedTags
     */
    public function stripTags(string|array|null $allowedTags = null): self
    {
        return $this->mutate(function () use ($allowedTags) {
            $this->htmlQueue->add(new StripTags($allowedTags));
        });
    }

    /**
     * Add classes to links, open external links in a new tab, etc.
     *
     * @param ?Closure(Link $link): mixed $callback
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
     * Obfuscate contact data to protect it from spam bots
     *
     * @param bool $email obfuscate email addresses
     * @param bool $phone obfuscate phone numbers
     */
    public function obfuscateContacts(
        bool $email = true,
        bool $phone = true
    ): self {
        return $this->mutate(function () use ($email, $phone) {
            $this->preserveEntities();
            $this->htmlQueue->add(new ObfuscateContacts($email, $phone));
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
        $html = $this->domQueue->applyTo($html);
        $html = $this->htmlQueue->applyTo($html);

        return $html;
    }

    /**
     * Dump the current state
     */
    public function dump(): self
    {
        dump($this->apply());

        return $this;
    }

    /**
     * Dump the current state and die
     */
    public function dd(): never
    {
        dump($this->apply());
        throw new DumpAndDieException();
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
