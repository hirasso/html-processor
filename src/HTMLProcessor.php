<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use Asika\Autolink\Autolink;
use Asika\Autolink\AutolinkOptions;
use DOMNode;
use DOMXPath;
use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\HTML5DOMElement;

final class HTMLProcessor
{
    protected HTML5DOMDocument $document;
    protected DOMXPath $xPath;
    protected string $originalHTML;

    /** @var Operation[] */
    protected array $operations = [];

    protected bool $isProcessed = false;

    protected function __construct(string $html)
    {
        $this->originalHTML = $html;
        $this->initializeDOM($html);
    }

    /**
     * Create an instance from a string of HTML
     */
    public static function fromString(?string $html = ''): self
    {
        return new self($html);
    }

    /**
     * Initialize the DOM without processing
     */
    protected function initializeDOM(string $html): void
    {
        $this->document = new HTML5DOMDocument();
        $this->document->loadHTML(
            htmlspecialchars_decode(Helpers::htmlentities($html)),
            HTML5DOMDocument::ALLOW_DUPLICATE_IDS,
        );
        $this->xPath = new DOMXPath($this->document);
    }

    /**
     * Query the document via XPath
     */
    public function queryXPath(string $expression, ?DOMNode $contextNode = null)
    {
        $this->process();
        return $this->xPath->query($expression, $contextNode);
    }

    /**
     * @return HTML5DOMElement[]
     */
    public function queryAll(string $selector): array
    {
        $this->process();
        return [...$this->document->querySelectorAll($selector)];
    }

    /**
     * Makes urls clickable
     */
    public function autolink(?AutolinkOptions $options = null): self
    {
        $this->operations[] = new Operation(
            type: OperationType::HTML,
            name: 'autolink',
            handler: function(string $html) use ($options): string {
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
        );

        $this->isProcessed = false;
        return $this;
    }

    /**
     * Automatically link @foobar or #hashtag to a social network (or anywhere)
     */
    public function linkToSocial(string $prefix, string $url): self
    {
        $this->operations[] = new Operation(
            type: OperationType::DOM,
            name: 'linkToSocial',
            handler: function($document) use ($prefix, $url): void {
                $linker = new SocialLinker($document);
                $linker->link($prefix, $url);
            }
        );

        $this->isProcessed = false;
        return $this;
    }

    /**
     * Add classes to links, open external links in a new tab, etc.
     *
     * @param callable(HTML5DOMElement): mixed $callback
     */
    public function processLinks(?callable $callback = null): self
    {
        $this->operations[] = new Operation(
            type: OperationType::DOM,
            name: 'processLinks',
            handler: function(HTML5DOMDocument $doc) use ($callback): void {
                LinkProcessor::process($doc, $callback);
            }
        );

        $this->isProcessed = false;
        return $this;
    }

    /**
     * Removes empty paragraphs from the DOM
     */
    public function beautify(
        ?bool $removeEmptyParagraphs = true,
        ?bool $preventWidows = true
    ): self {
        $this->operations[] = new Operation(
            type: OperationType::DOM,
            name: 'beautify',
            handler: function(HTML5DOMDocument $doc) use ($removeEmptyParagraphs, $preventWidows): void {
                $beautifier = new Beautifier($doc);

                if ($removeEmptyParagraphs) {
                    $beautifier->removeEmptyParagraphs();
                }

                if ($preventWidows) {
                    $beautifier->preventWidows();
                }
            }
        );

        $this->isProcessed = false;
        return $this;
    }

    /**
     * Localize quotes based on locale
     */
    public function localizeQuotes(
        string $locale,
        ?bool $debug = false
    ): self {
        $this->operations[] = new Operation(
            type: OperationType::DOM,
            name: 'localizeQuotes',
            handler: function(HTML5DOMDocument $doc) use ($locale, $debug): void {
                $localizer = new QuoteLocalizer($doc, $locale, $debug);
                $localizer->localize();
            }
        );

        $this->isProcessed = false;
        return $this;
    }

    /**
     * Encode Email addresses to protect them from spam bots
     */
    public function encodeEmails(): self
    {
        $this->operations[] = new Operation(
            type: OperationType::HTML,
            name: 'encodeEmails',
            handler: function(string $html): string {
                $encoder = new EmailEncoder();
                return $encoder->encode($html);
            }
        );

        $this->isProcessed = false;
        return $this;
    }

    /**
     * Execute all queued operations in optimal order
     */
    protected function process(): void
    {
        if ($this->isProcessed || empty($this->operations)) {
            return;
        }

        // Separate operations by type for optimal execution
        $domOperations = array_filter(
            $this->operations,
            fn(Operation $op) => $op->type === OperationType::DOM
        );
        $htmlOperations = array_filter(
            $this->operations,
            fn(Operation $op) => $op->type === OperationType::HTML
        );

        // Execute all DOM operations first (no serialization needed)
        foreach ($domOperations as $operation) {
            ($operation->handler)($this->document);
        }

        // Then execute HTML operations (only one serialize/parse cycle)
        if (!empty($htmlOperations)) {
            $html = $this->extractBodyHTML();

            foreach ($htmlOperations as $operation) {
                $html = ($operation->handler)($html);
            }

            // Re-parse only once after all HTML operations
            $this->initializeDOM($html);
        }

        $this->isProcessed = true;
    }

    /**
     * Extract HTML from body
     */
    protected function extractBodyHTML(): string
    {
        $html = $this->document->saveHTML();
        preg_match('/<body[^>]*>(?<content>.*?)<\/body>/is', $html, $matches);
        $html = $matches['content'] ?? '';
        $html = html_entity_decode($html);
        $html = str_replace('="__BOOLEAN_TRUE__"', '', $html);
        return $html;
    }

    /**
     * Convert the document to a string and return it (triggers lazy processing)
     */
    public function toHTML(): string
    {
        $this->process();
        return $this->extractBodyHTML();
    }

    /**
     * Magic method to directly echo the document
     */
    public function __toString(): string
    {
        return $this->toHTML();
    }

    /**
     * Reset all operations and return to original HTML
     */
    public function reset(): self
    {
        $this->operations = [];
        $this->initializeDOM($this->originalHTML);
        $this->isProcessed = false;

        return $this;
    }

    /**
     * Get the names of all queued operations (useful for debugging)
     *
     * @return string[]
     */
    public function getQueuedOperations(): array
    {
        return array_map(fn(Operation $op) => $op->name, $this->operations);
    }
}
