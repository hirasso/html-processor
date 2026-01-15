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
use Hirasso\HTMLProcessor\Support\Helpers;
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
            'en' => fn (string $s) => $this->entitiesToPlaceholders("“{$s}”"),
            'de' => fn (string $s) => $this->entitiesToPlaceholders("„{$s}“"),
            // French has narrow non-breaking spaces between the quotes and the word
            'fr' => fn (string $s) => $this->entitiesToPlaceholders("«\u{202F}{$s}\u{202F}»"),
        ];

        $this->singleQuoteReplacements = [
            'en' => fn (string $s) => $this->entitiesToPlaceholders("‘{$s}’"),
            'de' => fn (string $s) => $this->entitiesToPlaceholders("‚{$s}‘"),
            // French has narrow non-breaking spaces between the quotes and the word
            'fr' => fn (string $s) => $this->entitiesToPlaceholders("‹\u{202F}{$s}\u{202F}›"),
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

        $doubleQuoteChars = ['“', '”', '„', '«', '»'];
        $singleQuoteChars = ['‘', '’', '‚', '‹', '›'];

        $doubleQuoteEntity = $this->entitiesToPlaceholders('"');
        $singleQuoteEntity = $this->entitiesToPlaceholders("'");

        $doubleQuoteSearch = array_map([$this, 'entitiesToPlaceholders'], $doubleQuoteChars);
        $singleQuoteSearch = array_map([$this, 'entitiesToPlaceholders'], $singleQuoteChars);

        if (!$textNodes = (new DOMXPath($document))->query('//text()')) {
            return;
        }

        foreach ($textNodes as $textNode) {
            $nodeValue = $textNode->nodeValue ?? '';

            if (empty(trim($nodeValue))) {
                continue;
            }

            $text = $this->entitiesToPlaceholders($nodeValue);

            // Normalize all quotes to a consistent representation
            $text = str_replace($doubleQuoteSearch, $doubleQuoteEntity, $text);
            $text = str_replace($singleQuoteSearch, $singleQuoteEntity, $text);

            // Localize the quotes
            $text = $this->replaceQuoted($text, $doubleQuoteEntity, $this->doubleQuoteReplacements, $lang);
            $text = $this->replaceQuoted($text, $singleQuoteEntity, $this->singleQuoteReplacements, $lang);

            $textNode->nodeValue = $text;
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

        // Return the text unchainged if the provided language doesn't have replacements
        if (!isset($replacements[$lang])) {
            return $text;
        }

        /**
         * Escape the pattern so that html5-dom-document-internal-entity1-...-end
         * becomes html5\-dom\-document\-internal\-entity1\-...\-end
         */
        $escapedQuoteEntity = preg_quote($quoteEntity, '/');

        $result = preg_replace_callback(
            "/$escapedQuoteEntity(.*?)$escapedQuoteEntity/",
            fn ($matches) => $replacements[$lang]($matches[1]),
            $text
        );

        return $result ?? $text;
    }

    /**
     * Convert special chars to entities and then to the internal format used by HTML5DOMDocument
     */
    protected function entitiesToPlaceholders(string $str): string
    {
        $str = Helpers::htmlentities($str);
        $str = preg_replace('/&([a-zA-Z]+);/', 'html5-dom-document-internal-entity1-$1-end', $str) ?? $str;
        $str = preg_replace('/&#(\d+);/', 'html5-dom-document-internal-entity2-$1-end', $str) ?? $str;
        return $str;
    }
}
