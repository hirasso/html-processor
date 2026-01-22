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
        $text = str_replace(['‘', '’', '‚', '‹', '›'], "'", $text);
        $text = str_replace(['“', '”', '„', '«', '»'], '"', $text);

        $text = $this->replace($text, "'", $this->single);
        $text = $this->replace($text, '"', $this->double);
        return $text;
    }

    /**
     * Replace a string with a callback
     */
    private function replace(string $text, string $search, Closure $callback): string
    {
        return preg_replace_callback(
            "/(?<!\p{L})$search(.*?)$search(?!\p{L})/u",
            fn ($matches) => ($callback)($matches[1]),
            $text
        ) ?? $text;
    }
}
