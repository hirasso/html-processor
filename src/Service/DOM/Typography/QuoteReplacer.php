<?php

/*
* Copyright (c) 2025 Rasso Hilber
* https://rassohilber.com
*/

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Typography;

use Closure;

final readonly class QuoteReplacer
{
    private const SINGLE_QUOTES = ['‘', '’', '‚', '‹', '›'];
    private const DOUBLE_QUOTES = ['“', '”', '„', '«', '»'];
    private const STANDARD_SINGLE_QUOTE = "'";
    private const STANDARD_DOUBLE_QUOTE = '"';

    /** before and after quotes */
    private string $matchBefore;
    private string $matchAfter;

    public function __construct(
        public string $lang,
        public Closure $single,
        public Closure $double
    ) {
        $this->matchBefore = '(?<!\p{L})';
        $this->matchAfter = '(?!\p{L})';
    }

    /**
     * Apply this replacer to a string
     */
    public function applyTo(string $text): string
    {
        $text = $this->normalize($text, self::SINGLE_QUOTES, self::STANDARD_SINGLE_QUOTE);
        $text = $this->normalize($text, self::DOUBLE_QUOTES, self::STANDARD_DOUBLE_QUOTE);

        $text = $this->localize(self::STANDARD_SINGLE_QUOTE, $this->single, $text);
        $text = $this->localize(self::STANDARD_DOUBLE_QUOTE, $this->double, $text);

        return $text;
    }

    /**
     * @param string[] $quotes
     */
    private function normalize(string $text, array $quotes, string $replace): string
    {
        /** convert the array of quotes into a character class like [”|“|›] */
        $class = '[' . preg_quote(implode('', $quotes), '/') . ']';

        /** Match quotes not preceded by letter OR not followed by letter */
        $pattern = "/(?:{$this->matchBefore}{$class}|{$class}{$this->matchAfter})/u";

        return preg_replace($pattern, $replace, $text) ?? $text;
    }

    /**
     * Localize wrapping quotes with the localized version
     */
    private function localize(string $quote, Closure $callback, string $text): string
    {
        return preg_replace_callback(
            "/{$this->matchBefore}{$quote}(.*){$quote}{$this->matchAfter}/u",
            fn ($matches) => ($callback)($matches[1]),
            $text
        ) ?? $text;
    }
}
