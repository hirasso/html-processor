<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use Asika\Autolink\AutolinkOptions;
use Closure;
use Dom\HTMLDocument;
use Hirasso\HTMLProcessor\Exceptions\DumpAndDieException;
use Hirasso\HTMLProcessor\Queue\DomQueue;
use Hirasso\HTMLProcessor\Queue\HTMLQueue;
use Hirasso\HTMLProcessor\Service\Dom\AutolinkUrlsService;
use Hirasso\HTMLProcessor\Service\Dom\DomMutationService;
use Hirasso\HTMLProcessor\Service\Dom\ProcessLinksService\Link;
use Hirasso\HTMLProcessor\Service\Dom\ProcessLinksService\ProcessLinksService;
use Hirasso\HTMLProcessor\Service\Dom\LinkPrefixService;
use Hirasso\HTMLProcessor\Service\Dom\RemoveEmptyElementsService;
use Hirasso\HTMLProcessor\Service\HTML\StripTags;

/**
 * Process a HTML string using a fluent API
 * @see https://github.com/hirasso/html-processor
 */
final class HTMLProcessor
{
    private DomQueue $domQueue;
    private HTMLQueue $htmlQueue;

    private function __construct(
        private readonly string $originalHTML
    ) {
        $this->domQueue = new DomQueue();
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
     * Add a mutation to the Dom queue
     *
     * @param Closure(HTMLDocument $document): mixed $mutation
     * @param int $prio high is later in the queue
     */
    public function mutate(Closure $mutation, int $prio = 0): self
    {
        $this->domQueue->add(new DomMutationService($mutation, $prio));
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
     * Execute all queued services in optimal order
     *
     * @return string – the processed HTML string
     */
    public function apply(): string
    {
        if (empty($this->originalHTML)) {
            return $this->originalHTML;
        }

        $html = $this->originalHTML;
        $html = $this->htmlQueue->applyTo($html);
        $html = $this->domQueue->applyTo($html);

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
