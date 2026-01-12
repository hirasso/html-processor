<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use Asika\Autolink\AutolinkOptions;
use Closure;
use Hirasso\HTMLProcessor\Enum\UrlType;
use IvoPetkov\HTML5DOMDocument;
use Hirasso\HTMLProcessor\Support\Helpers;
use Hirasso\HTMLProcessor\Queue\DOMQueue;
use Hirasso\HTMLProcessor\Queue\HTMLQueue;
use Hirasso\HTMLProcessor\Service\DOM\EmptyElements;
use Hirasso\HTMLProcessor\Service\DOM\LinkProcessor;
use Hirasso\HTMLProcessor\Service\DOM\PrefixLinker;
use Hirasso\HTMLProcessor\Service\HTML\Autolinker;
use Hirasso\HTMLProcessor\Service\HTML\EmailEncoder;
use Hirasso\HTMLProcessor\Service\DOM\Typography\WidowPreventer;
use Hirasso\HTMLProcessor\Service\DOM\Typography\QuoteLocalizer;

final class HTMLProcessor
{
    /** track if entities should be decoded */
    protected bool $preserveEntities = false;

    /** Track if duplicate IDs should be allowed in the HTML */
    private bool $allowDuplicateIDs = LIBXML_VERSION < 21000;

    /** used for typography optimizations */
    protected string $locale = 'en_US';

    protected DOMQueue $domQueue;
    protected HTMLQueue $htmlQueue;

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
     * Make urls clickable
     */
    public function autolinkUrls(?AutolinkOptions $options = null): self
    {
        $this->htmlQueue->add(new Autolinker($options  ?? new AutolinkOptions(
            stripScheme: true,
            textLimit: 35,
            autoTitle: false,
            escape: true,
            linkNoScheme: true
        )));

        return $this;
    }

    /**
     * Automatically link @foobar or #hashtag to a social network (or anywhere)
     */
    public function autolinkPrefix(string $prefix, string $url): self
    {
        $linker = $this->domQueue->get(PrefixLinker::class)
            ?? new PrefixLinker();

        $linker->register($prefix, $url);

        $this->domQueue->add($linker);

        return $this;
    }

    /**
     * Add classes to links, open external links in a new tab, etc.
     *
     * @param ?Closure(\IvoPetkov\HTML5DOMElement $el, UrlType $type): mixed $postProcess – post-process links with information
     */
    public function processLinks(
        ?Closure $postProcess = null,
        ?bool $addClasses = null,
    ): self {
        $this->domQueue->add(new LinkProcessor($postProcess, $addClasses ?? true));
        return $this;
    }

    /**
     * Remove empty elements
     */
    public function removeEmptyElements(?string $selector = null): self
    {
        $this->domQueue->add(new EmptyElements($selector));
        return $this;
    }

    /**
     * Optimize typography
     */
    public function typography(
        ?string $locale = null,
        ?bool $localizeQuotes = true,
        ?bool $preventWidows = true,
    ): self {

        $localizeQuotes && $this->domQueue->add(new QuoteLocalizer($locale ?? 'en_US'));
        $preventWidows && $this->domQueue->add(new WidowPreventer());

        return $this;
    }

    /**
     * Encode Email addresses to protect them from spam bots
     */
    public function encodeEmails(): self
    {
        $this->preserveEntities = true;
        $this->htmlQueue->add(new EmailEncoder());
        return $this;
    }

    /**
     * Check if there are any operations registered
     */
    protected function hasOperations(): bool
    {
        return !$this->htmlQueue->isEmpty() || !$this->domQueue->isEmpty();
    }

    /**
     * Execute all queued operations in optimal order
     *
     * @return string – the processed HTML string
     */
    public function apply(): string
    {
        if (empty($this->originalHTML) || !$this->hasOperations()) {
            return $this->originalHTML;
        }

        $html = $this->originalHTML;
        $html = $this->runHTMLQueue($html);
        $html = $this->runDOMQueue($html);

        if (!$this->preserveEntities) {
            return html_entity_decode($html);
        }

        // When preserving entities, only decode htmlspecialchars (&lt; &gt; &amp; &quot;)
        // while keeping numeric entities (&#109; &#x6d; &nbsp; etc.)
        return htmlspecialchars_decode($html);
    }

    /**
     * Run operations against the raw HTML
     */
    protected function runHTMLQueue(string $html): string
    {
        foreach ($this->htmlQueue->all() as $service) {
            $html = $service->run($html);
        }

        return $html;
    }

    /**
     * Run operations against DOMDocument
     */
    protected function runDOMQueue(string $html): string
    {
        if ($this->domQueue->isEmpty()) {
            return $html;
        }

        $document = new HTML5DOMDocument();
        $document->loadHTML(
            htmlspecialchars_decode(Helpers::htmlentities($html)),
            $this->allowDuplicateIDs
                ? HTML5DOMDocument::ALLOW_DUPLICATE_IDS
                : 0,
        );

        // Execute all DOM services
        foreach ($this->domQueue->all() as $service) {
            $service->run($document);
        }
        return Helpers::extractBodyHTML($document);
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
    public function preserveEntities(?bool $preserve = true): self
    {
        $this->preserveEntities = $preserve ?? true;
        return $this;
    }
}
