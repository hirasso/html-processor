<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use Asika\Autolink\Autolink;
use Asika\Autolink\AutolinkOptions;
use Hirasso\HTMLProcessor\Operations\DOMOperation;
use Hirasso\HTMLProcessor\Operations\DOMOperations;
use Hirasso\HTMLProcessor\Operations\HTMLOperation;
use Hirasso\HTMLProcessor\Operations\HTMLOperations;
use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\HTML5DOMElement;

final class HTMLProcessor
{
    /** track if entities should be decoded */
    protected bool $decodeEntities = true;

    protected DOMOperations $domOperations;
    protected HTMLOperations $htmlOperations;

    protected function __construct(
        protected readonly string $originalHTML
    ) {
        $this->domOperations = new DOMOperations();
        $this->htmlOperations = new HTMLOperations();
    }

    /**
     * Create an instance from a string of HTML
     */
    public static function fromString(?string $html = ''): self
    {
        return new self($html);
    }

    /**
     * Makes urls clickable
     */
    public function autolink(?AutolinkOptions $options = null): self
    {
        $this->htmlOperations->add(new HTMLOperation(
            name: 'autolink',
            handler: function (string $html) use ($options): string {
                $autolink = new Autolink($options ?? new AutolinkOptions(
                    stripScheme: true,
                    textLimit: 35,
                    autoTitle: false,
                    escape: true,
                    linkNoScheme: true
                ));

                $html = $autolink->convert($html);
                $html = $autolink->convertEmail($html);

                return $html;
            }
        ));
        return $this;
    }

    /**
     * Automatically link @foobar or #hashtag to a social network (or anywhere)
     */
    public function linkToSocial(string $prefix, string $url): self
    {
        $this->domOperations->add(new DOMOperation(
            name: 'linkToSocial',
            handler: function ($document) use ($prefix, $url): void {
                $linker = new SocialLinker($document);
                $linker->link($prefix, $url);
            }
        ));
        return $this;
    }

    /**
     * Add classes to links, open external links in a new tab, etc.
     *
     * @param callable(HTML5DOMElement): mixed $callback
     */
    public function processLinks(?callable $callback = null): self
    {
        $this->domOperations->add(new DOMOperation(
            name: 'processLinks',
            handler: function (HTML5DOMDocument $doc) use ($callback): void {
                LinkProcessor::process($doc, $callback);
            }
        ));
        return $this;
    }

    /**
     * Removes empty paragraphs from the DOM
     */
    public function beautify(
        ?bool $removeEmptyParagraphs = true,
        ?bool $preventWidows = true
    ): self {
        $this->domOperations->add(new DOMOperation(
            name: 'beautify',
            handler: function (HTML5DOMDocument $doc) use ($removeEmptyParagraphs, $preventWidows): void {
                $beautifier = new Beautifier($doc);

                if ($removeEmptyParagraphs) {
                    $beautifier->removeEmptyParagraphs();
                }

                if ($preventWidows) {
                    $beautifier->preventWidows();
                }
            }
        ));
        return $this;
    }

    /**
     * Localize quotes based on locale
     */
    public function localizeQuotes(
        string $locale,
        ?bool $debug = false
    ): self {
        $this->domOperations->add(new DOMOperation(
            name: 'localizeQuotes',
            handler: function (HTML5DOMDocument $doc) use ($locale, $debug): void {
                $localizer = new QuoteLocalizer($doc, $locale, $debug);
                $localizer->localize();
            }
        ));
        return $this;
    }

    /**
     * Encode Email addresses to protect them from spam bots
     */
    public function encodeEmails(): self
    {
        $this->htmlOperations->add(new HTMLOperation(
            name: 'encodeEmails',
            handler: function (string $html): string {
                /** Do not decode entities, otherwise the encoding would be lost */
                $this->decodeEntities = false;

                $encoder = new EmailEncoder();
                return $encoder->encode($html);
            }
        ));
        return $this;
    }

    /**
     * Check if there are any operations registered
     */
    protected function hasOperations(): bool {
        return !$this->htmlOperations->isEmpty() || !$this->domOperations->isEmpty();
    }

    /**
     * Execute all queued operations in optimal order
     *
     * @return string – the processed HTML string
     */
    public function process(): string
    {
        if (empty($this->originalHTML) || !$this->hasOperations()) {
            return $this->originalHTML;
        }

        $html = $this->runHTMLOperations($this->originalHTML);

        $html = $this->runDOMOperations($html);

        return $this->decodeEntities
            ? html_entity_decode($html)
            : $html;
    }

    /**
     * Run operations against the raw HTML
     */
    protected function runHTMLOperations(string $html): string
    {
        foreach ($this->htmlOperations->all() as $operation) {
            $html = ($operation->handler)($html);
        }

        return $html;
    }

    protected function runDOMOperations(string $html): string
    {
        if ($this->domOperations->isEmpty()) {
            return $html;
        }

        $document = new HTML5DOMDocument();
        $document->loadHTML(
            htmlspecialchars_decode(Helpers::htmlentities($html)),
            HTML5DOMDocument::ALLOW_DUPLICATE_IDS,
        );

        // Execute all DOM operations
        foreach ($this->domOperations->all() as $operation) {
            ($operation->handler)($document);
        }
        return Helpers::extractBodyHTML($document);
    }

    /**
     * Magic method to directly echo the document
     */
    public function __toString(): string
    {
        return $this->process();
    }
}
