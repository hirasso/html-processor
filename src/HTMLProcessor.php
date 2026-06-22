<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use Asika\Autolink\AutolinkOptions;
use Closure;
use Hirasso\HTMLProcessor\Exceptions\DumpAndDieException;
use Hirasso\HTMLProcessor\Queue\DOMQueue;
use Hirasso\HTMLProcessor\Queue\HTMLQueue;
use Hirasso\HTMLProcessor\Service\DOM\AutolinkUrlsService;
use Hirasso\HTMLProcessor\Service\DOM\ProcessLinksService\Link;
use Hirasso\HTMLProcessor\Service\DOM\ProcessLinksService\ProcessLinksService;
use Hirasso\HTMLProcessor\Service\DOM\LinkPrefixService;
use Hirasso\HTMLProcessor\Service\DOM\RemoveEmptyElementsService;
use Hirasso\HTMLProcessor\Service\DOM\ObfuscateEmailsService;
use Hirasso\HTMLProcessor\Service\HTML\StripTags;

/**
 * Process a HTML string using a fluent API
 * @see https://github.com/hirasso/html-processor
 */
final class HTMLProcessor
{
    private DOMQueue $domQueue;
    private HTMLQueue $htmlQueue;

    private function __construct(
        private readonly string $originalHTML
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
     * Make urls clickable
     */
    public function autolinkUrls(?AutolinkOptions $options = null): self
    {
        $this->domQueue->add(new AutolinkUrlsService($options  ?? new AutolinkOptions(
            stripScheme: true,
            textLimit: 35,
            autoTitle: false,
            escape: true,
            // poses issues with e.g. "Architekt.innen"
            linkNoScheme: false
        )));

        return $this;
    }

    /**
     * Automatically link @foobar or #hashtag to a social network (or anywhere)
     */
    public function autolinkPrefix(string $prefix, string $url): self
    {
        $linker = $this->domQueue->get(LinkPrefixService::class)
            ?? new LinkPrefixService();

        $linker->register($prefix, $url);

        $this->domQueue->add($linker);

        return $this;
    }

    /**
     * @param string|list<string>|null $allowedTags
     */
    public function stripTags(string|array|null $allowedTags = null): self
    {
        $this->htmlQueue->add(new StripTags($allowedTags));

        return $this;
    }

    /**
     * Add classes to links, open external links in a new tab, etc.
     *
     * @param ?Closure(Link $link): mixed $callback
     */
    public function processLinks(?Closure $callback = null): self
    {
        $this->domQueue->add(new ProcessLinksService($callback));

        return $this;
    }

    /**
     * Remove empty elements
     */
    public function removeEmptyElements(string $selector): self
    {
        $this->domQueue->add(new RemoveEmptyElementsService($selector));

        return $this;
    }

    /**
     * Obfuscate emails in plaintext and mailto: links
     */
    public function obfuscate(
    ): self {
        $this->domQueue->add(new ObfuscateEmailsService());

        return $this;
    }

    /**
     * Conditionally apply operations
     *
     * @param bool|Closure(self): bool $condition
     * @param Closure(self): mixed $then
     * @param ?Closure(self): mixed $else
     */
    public function when(
        bool|Closure $condition,
        Closure $then,
        ?Closure $else = null
    ): self {
        if ($condition instanceof Closure) {
            $condition = $condition($this);
        }

        if ($condition) {
            $then($this);
        } elseif ($else !== null) {
            $else($this);
        }

        return $this;
    }

    /**
     * Execute all queued operations in optimal order
     *
     * @return string – the processed HTML string
     */
    public function apply(): string
    {
        if (empty($this->originalHTML)) {
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
}
