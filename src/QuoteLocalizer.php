<?php

/*
* Copyright (c) 2025 Rasso Hilber
* https://rassohilber.com
*/

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use DOMXPath;
use IvoPetkov\HTML5DOMDocument;

/**
 * Localize single and double quotes to the correct format in various languages
 * Usage: $html = QuoteLocalizer::localize($html, get_locale());
 * Supported languages: English, German, French
 */
final readonly class QuoteLocalizer
{
    protected string $languageCode;
    protected string $countryCode;

    public function __construct(
        protected HTML5DOMDocument $document,
        string $locale,
        protected bool $debug
    ) {
        $separator = str_contains($locale, '_') ? '_' : '-';
        [$this->languageCode, $this->countryCode] = explode($separator, $locale, 2);
    }

    /**
     * Run the normalizer
     */
    public function localize()
    {
        $doubleQuoteChars = ['“', '”', '„', '«', '»'];
        $singleQuoteChars = ['‘', '’', '‚', '‹', '›'];

        $doubleQuoteEntity = $this->entitiesToPlaceholders('"');
        $singleQuoteEntity = $this->entitiesToPlaceholders("'");

        $doubleQuoteSearch = array_map([$this, 'entitiesToPlaceholders'], $doubleQuoteChars);
        $singleQuoteSearch = array_map([$this, 'entitiesToPlaceholders'], $singleQuoteChars);

        $doubleQuoteReplacements = [
            'de' => fn (string $s) => $this->entitiesToPlaceholders("„{$s}“"),
            'en' => fn (string $s) => $this->entitiesToPlaceholders("“{$s}”"),
            // French has narrow non-breaking spaces between the quotes and the word
            'fr' => fn (string $s) => $this->entitiesToPlaceholders("«\u{202F}{$s}\u{202F}»"),
        ];

        $singleQuoteReplacements = [
            'de' => fn (string $s) => $this->entitiesToPlaceholders("‚{$s}‘"),
            'en' => fn (string $s) => $this->entitiesToPlaceholders("‘{$s}’"),
            // French has narrow non-breaking spaces between the quotes and the word
            'fr' => fn (string $s) => $this->entitiesToPlaceholders("‹\u{202F}{$s}\u{202F}›"),
        ];

        $xPath = new DOMXPath($this->document);

        foreach ($xPath->query('//text()') as $textNode) {
            if (trim($textNode->nodeValue) === '') {
                continue;
            }

            $text = $this->entitiesToPlaceholders($textNode->nodeValue);

            // Normalize all quotes to a consistent representation
            $text = str_replace($doubleQuoteSearch, $doubleQuoteEntity, $text);
            $text = str_replace($singleQuoteSearch, $singleQuoteEntity, $text);

            // Localize the quotes
            $text = $this->replaceQuoted($text, $doubleQuoteEntity, $doubleQuoteReplacements);
            $text = $this->replaceQuoted($text, $singleQuoteEntity, $singleQuoteReplacements);

            $textNode->nodeValue = $text;
        }
    }

    /**
     * Replace quotes based on language
     */
    private function replaceQuoted(
        string $text,
        string $quoteEntity,
        array $replacements
    ): string {
        $lang = $this->languageCode;

        /**
         * Escape the pattern so that html5-dom-document-internal-entity1-...-end
         * becomes html5\-dom\-document\-internal\-entity1\-...\-end
         */
        $escapedQuoteEntity = preg_quote($quoteEntity, '/');

        return preg_replace_callback(
            "/$escapedQuoteEntity(.*?)$escapedQuoteEntity/",
            fn ($matches) => isset($replacements[$lang]) ? $replacements[$lang]($matches[1]) : $matches[0],
            $text
        );
    }

    /**
     * Convert special chars to entities and then to the internal format used by HTML5DOMDocument
     */
    protected function entitiesToPlaceholders(string $str): string
    {
        $str = Helpers::htmlentities($str);
        $str = preg_replace('/&([a-zA-Z]+);/', 'html5-dom-document-internal-entity1-$1-end', $str);
        $str = preg_replace('/&#(\d+);/', 'html5-dom-document-internal-entity2-$1-end', $str);
        return $str;
    }
}
