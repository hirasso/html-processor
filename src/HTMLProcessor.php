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

        $document = new HTML5DOMDocument();
        $document->loadHTML(
            htmlspecialchars_decode(Helpers::htmlentities($this->originalHTML)),
            HTML5DOMDocument::ALLOW_DUPLICATE_IDS,
        );

        // Separate operations by type for optimal execution
        $domOperations = array_filter(
            $this->operations,
            fn (Operation $op) => $op->type === OperationType::DOM
        );
        $htmlOperations = array_filter(
            $this->operations,
            fn (Operation $op) => $op->type === OperationType::HTML
        );

        // Execute all DOM operations first (no serialization needed)
        foreach ($domOperations as $operation) {
            ($operation->handler)($document);
        }

        $html = Helpers::extractBodyHTML($document);

        // Then execute HTML operations (only one serialize/parse cycle)
        if (!empty($htmlOperations)) {

            foreach ($htmlOperations as $operation) {
                $html = ($operation->handler)($html);
            }
        }

        return $html;
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
