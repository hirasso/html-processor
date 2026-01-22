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
    public function __construct(
        public string $lang,
        public Closure $single,
        public Closure $double
    ) {

    }

    /**
     * Apply this replacer to a string
     */
    public function apply(string $text): string
    {
        $text = $this->normalize($text, ['‘', '’', '‚', '‹', '›'], "'");
        $text = $this->normalize($text, ['“', '”', '„', '«', '»'], '"');

        $text = $this->localize("'", $this->single, $text);
        $text = $this->localize('"', $this->double, $text);

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
        $pattern = "/(?:(?<!\p{L})$class|$class(?!\p{L}))/u";

        return preg_replace($pattern, $replace, $text) ?? $text;
    }

    /**
     * Replace a string with a callback
     * @param string|string[] $search
     */
    private function localize(string|array $search, Closure $callback, string $text): string
    {
        /** always convert to array */
        $search = array_map(
            fn (string $s) => "/(?<!\p{L})$s(.*?)$s(?!\p{L})/u",
            is_array($search) ? $search : [$search]
        );

        return preg_replace_callback(
            $search,
            fn ($matches) => ($callback)($matches[1]),
            $text
        ) ?? $text;
    }
}
