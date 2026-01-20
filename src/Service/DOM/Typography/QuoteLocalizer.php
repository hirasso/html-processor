<?php

/*
* Copyright (c) 2025 Rasso Hilber
* https://rassohilber.com
*/

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Typography;

use Closure;
use DOMXPath;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use IvoPetkov\HTML5DOMDocument;

/**
 * Localize single and double quotes to the correct format in various languages
 * Usage: $html = QuoteLocalizer::localize($html, get_locale());
 * Supported languages: English, German, French
 */
final class QuoteLocalizer implements DOMServiceContract
{
    /** @var array<string, Closure(string): string> */
    private array $doubleQuoteReplacements;

    /** @var array<string, Closure(string): string> */
    private array $singleQuoteReplacements;

    public function prio(): int
    {
        return 0;
    }

    public function __construct(private Typography $typography)
    {
        $this->doubleQuoteReplacements = [
            'en' => fn (string $s) => "“{$s}”",
            'de' => fn (string $s) => "„{$s}“",
            // French has narrow non-breaking spaces between the quotes and the word
            'fr' => fn (string $s) => "«\u{202F}{$s}\u{202F}»",
        ];

        $this->singleQuoteReplacements = [
            'en' => fn (string $s) => "‘{$s}’",
            'de' => fn (string $s) => "‚{$s}‘",
            // French has narrow non-breaking spaces between the quotes and the word
            'fr' => fn (string $s) => "‹\u{202F}{$s}\u{202F}›",
        ];
    }

    /**
     * Check if a language is supported
     */
    private function isLanguageSupported(string $lang): bool
    {
        return array_key_exists($lang, $this->doubleQuoteReplacements)
            && array_key_exists($lang, $this->singleQuoteReplacements);
    }

    /**
     * Run the normalizer
     */
    public function run(HTML5DOMDocument $document): void
    {
        $lang = $this->typography->getLanguageCode();

        if (!$lang || !$this->isLanguageSupported($lang)) {
            return;
        }

        $doubleQuoteSearch = ['“', '”', '„', '«', '»'];
        $singleQuoteSearch = ['‘', '’', '‚', '‹', '›'];

        $doubleQuote = '"';
        $singleQuote = "'";

        if (!$textNodes = (new DOMXPath($document))->query('//text()')) {
            return;
        }

        foreach ($textNodes as $textNode) {
            $nodeValue = $textNode->nodeValue ?? '';

            if (empty(trim($nodeValue))) {
                continue;
            }

            $text = Support::decode($nodeValue);

            // Normalize all quotes to a consistent representation
            $text = str_replace($doubleQuoteSearch, $doubleQuote, $text);
            $text = str_replace($singleQuoteSearch, $singleQuote, $text);

            // Localize the quotes
            $text = $this->replaceQuoted($text, $doubleQuote, $this->doubleQuoteReplacements, $lang);
            $text = $this->replaceQuoted($text, $singleQuote, $this->singleQuoteReplacements, $lang);

            $textNode->nodeValue = Support::encode($text, usePlaceholders: true);
        }
    }

    /**
     * Replace quotes based on language
     *
     * @param array<string, Closure(string): string> $replacements
     */
    private function replaceQuoted(
        string $text,
        string $quoteEntity,
        array $replacements,
        string $lang
    ): string {

        // Return the text unchanged if the provided language doesn't have replacements
        if (!isset($replacements[$lang])) {
            return $text;
        }

        /**
         * Escape the pattern so that html5-dom-document-internal-entity1-...-end
         * becomes html5\-dom\-document\-internal\-entity1\-...\-end
         */
        $escapedQuoteEntity = preg_quote($quoteEntity, '/');

        $text = preg_replace_callback(
            "/(?<!\p{L})$escapedQuoteEntity(.*?)$escapedQuoteEntity(?!\p{L})/u",
            fn ($matches) => $replacements[$lang]($matches[1]),
            $text
        ) ?? $text;

        return $text;
    }
}
