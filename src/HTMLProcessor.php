<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use Asika\Autolink\Autolink;
use Asika\Autolink\AutolinkOptions;
use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\HTML5DOMElement;

final class HTMLProcessor
{
    /** @var Operation[] */
    protected array $operations = [];

    protected function __construct(protected readonly string $originalHTML)
    {
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
        $this->operations[] = new Operation(
            type: OperationType::HTML,
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
        );
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
            handler: function ($document) use ($prefix, $url): void {
                $linker = new SocialLinker($document);
                $linker->link($prefix, $url);
            }
        );
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
            handler: function (HTML5DOMDocument $doc) use ($callback): void {
                LinkProcessor::process($doc, $callback);
            }
        );
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
            handler: function (HTML5DOMDocument $doc) use ($removeEmptyParagraphs, $preventWidows): void {
                $beautifier = new Beautifier($doc);

                if ($removeEmptyParagraphs) {
                    $beautifier->removeEmptyParagraphs();
                }

                if ($preventWidows) {
                    $beautifier->preventWidows();
                }
            }
        );
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
            handler: function (HTML5DOMDocument $doc) use ($locale, $debug): void {
                $localizer = new QuoteLocalizer($doc, $locale, $debug);
                $localizer->localize();
            }
        );
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
            handler: function (string $html): string {
                $encoder = new EmailEncoder();
                return $encoder->encode($html);
            }
        );
        return $this;
    }

    /**
     * Execute all queued operations in optimal order
     *
     * @return string – the processed HTML string
     */
    public function process(): string
    {
        if (empty($this->originalHTML) || empty($this->operations)) {
            return $this->originalHTML;
        }

        $html = $this->runHTMLOperations($this->originalHTML);

        $html = $this->runDOMOperations($html);

        return html_entity_decode($html);
    }

    protected function runHTMLOperations(string $html): string {
        $operations = $this->filterOperations(OperationType::HTML);

        if (empty($operations)) {
            return $html;
        }

        // Run operations against the raw HTML
        foreach ($operations as $operation) {
            $html = ($operation->handler)($html);
        }

        return $html;
    }

    protected function runDOMOperations(string $html): string {
        $operations = $this->filterOperations(OperationType::DOM);

        if (empty($operations)) {
            return $html;
        }

        $document = new HTML5DOMDocument();
        $document->loadHTML(
            htmlspecialchars_decode(Helpers::htmlentities($html)),
            HTML5DOMDocument::ALLOW_DUPLICATE_IDS,
        );

        // Execute all DOM operations
        foreach ($operations as $operation) {
            ($operation->handler)($document);
        }
        return Helpers::extractBodyHTML($document);
    }

    /** @return Operation[] */
    protected function filterOperations(OperationType $type): array {
        return array_filter(
            $this->operations,
            fn ($op) => $op->type === $type
        );
    }

    /**
     * Magic method to directly echo the document
     */
    public function __toString(): string
    {
        return $this->process();
    }

    /**
     * Get the names of all queued operations (useful for debugging)
     *
     * @return string[]
     */
    public function getQueuedOperations(): array
    {
        return array_map(fn (Operation $op) => $op->name, $this->operations);
    }
}
