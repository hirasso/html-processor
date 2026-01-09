<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use Asika\Autolink\Autolink;
use Asika\Autolink\AutolinkOptions;
use DOMXPath;
use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\HTML5DOMElement;

final class HTMLProcessor
{
    protected HTML5DOMDocument $document;
    protected DOMXPath $xPath;

    protected function __construct(string $html)
    {
        $this->applyHTML($html);
    }

    /**
     * Create an instance from a string of HTML
     */
    public static function fromString(?string $html = ''): self
    {
        return new self($html);
    }

    /**
     * Convert a HTML string to a document
     */
    public function applyHTML(?string $html = ''): self
    {
        $this->document = new HTML5DOMDocument();
        $this->document->loadHTML(
            htmlspecialchars_decode($this->htmlentities($html)),
            HTML5DOMDocument::ALLOW_DUPLICATE_IDS,
        );
        $this->xPath = new DOMXPath($this->document);

        return $this;
    }

    /**
     * query the document's XPath
     */
    public function queryXPath(string $expression)
    {
        return $this->xPath->query($expression);
    }

    /**
     * @return HTML5DOMElement[]
     */
    public function queryAll(string $selector): array
    {
        return [...$this->document->querySelectorAll($selector)];
    }

    /**
     * Makes urls clickable
     */
    public function autolink(
        ?AutolinkOptions $options = null
    ): self {

        $autolink = new Autolink($options ?? new AutolinkOptions(
            /** strip the scheme in the link text */
            stripScheme: true,
            textLimit: null,
            autoTitle: false,
            escape: true,
            linkNoScheme: true
        ));

        $html = $autolink->convert($this->toHTML());
        $html = $autolink->convertEmail($html);
        $this->applyHTML($html);

        return $this;
    }

    /**
     * Magic method to directly echo the document
     */
    public function __toString(): string
    {
        return $this->toHTML();
    }

    /**
     * Convert the document to a string and return it
     */
    public function toHTML(): string
    {
        $html = $this->document->saveHTML();
        preg_match('/<body[^>]*>(?<content>.*?)<\/body>/is', $html, $matches);
        $html = $matches['content'] ?? '';
        $html = html_entity_decode($html);

        $html = str_replace('="__BOOLEAN_TRUE__"', '', $html);
        return $html;
    }

    /**
     * Convert entities, while preserving already-encoded entities.
     *
     * @link https://www.php.net/htmlentities Borrowed from the PHP Manual user notes.
     */
    public function htmlentities(string $text): string
    {
        $translation_table = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);

        $translation_table[chr(38)] = '&';

        return preg_replace(
            '/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/',
            '&amp;',
            strtr($text, $translation_table)
        );
    }

    /**
     * Automatically link @foobar and #hashtag to instagram
     */
    public function instagram(): self
    {
        InstagramLinker::link($this->document);

        return $this;
    }

    /**
     * - add classes to links
     * - open external links in a new tab
     *
     * @param callable(HTML5DOMElement): mixed $callback
     */
    public function processLinks(?callable $callback = null): self
    {
        LinkProcessor::process($this->document, $callback);
        return $this;
    }

    /**
     * Removes empty paragraphs from the DOM
     */
    public function beautify(): self
    {
        $beautifier = new Beautifier($this);
        $beautifier
            ->removeEmptyParagraphs()
            ->preventWidows();

        return $this;
    }

    /**
     * Localize quotes based on locale
     */
    public function localizeQuotes(
        string $locale,
        ?bool $debug = false
    ): self {
        $localizer = new QuoteLocalizer($this, $locale, $debug);
        $localizer->localize();

        return $this;
    }

    /**
     * Encode Email addresses to protect them from spam bots
     */
    public function encodeEmails(): string
    {
        $encoder = new EmailEncoder();
        return $encoder->encode($this->toHTML());
    }
}
