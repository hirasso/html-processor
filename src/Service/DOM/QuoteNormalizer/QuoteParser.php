<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

/**
 * Parses text into segments for quote wrapping.
 *
 * Uses stack-based matching for proper nesting:
 * Input:  "outer "inner" outer"
 * Output: [QuoteOpen, Text("outer "), QuoteOpen, Text("inner"), QuoteClose, Text(" outer"), QuoteClose]
 */
final class QuoteParser
{
    private QuoteFinder $finder;

    public function __construct()
    {
        $this->finder = new QuoteFinder();
    }

    /**
     * Parse text into segments.
     *
     * @return Segment[]
     */
    public function parse(string $text): array
    {
        // Phase 1: Reset existing curly quotes to standard ASCII "
        $text = $this->resetQuotes($text);

        // Phase 2: Find all quotes
        $quotes = $this->finder->find($text);

        // Phase 3: Assign roles to found quotes
        $this->assignRoles($quotes);

        // Phase 4: build and return segments
        return $this->buildSegments($text, $quotes);
    }

    /**
     * Reset curly quotes back to ASCII ", but only if they look like quotes
     * (not preceded by letter OR not followed by letter).
     * This preserves apostrophes like "don't" which are surrounded by letters.
     */
    private function resetQuotes(string $text): string
    {
        $matchBefore = '(?<!\p{L})';
        $matchAfter = '(?!\p{L})';

        $class = '[' . preg_quote(implode('', CurlyQuotes::all()), '/') . ']';
        $pattern = "/(?:{$matchBefore}{$class}|{$class}{$matchAfter})/u";

        return preg_replace($pattern, '"', $text) ?? $text;
    }

    /**
     * Assign Open/Close roles using a stack.
     * Unpaired opening quotes have their role cleared.
     *
     * @param QuoteMatch[] $quotes
     */
    private function assignRoles(array $quotes): void
    {
        /** @var QuoteMatch[] $stack */
        $stack = [];

        foreach ($quotes as $match) {
            if ($match->canClose && count($stack) > 0) {
                $match->role = QuoteRole::Close;
                array_pop($stack);
            } elseif ($match->canOpen) {
                $match->role = QuoteRole::Open;
                $stack[] = $match;
            }
        }

        // Clear unpaired opening quotes
        foreach ($stack as $unpaired) {
            $unpaired->role = null;
        }
    }

    /**
     * @param QuoteMatch[] $quotes
     * @return Segment[]
     */
    private function buildSegments(string $text, array $quotes): array
    {
        if (empty($text)) {
            return [];
        }

        $activeQuotes = array_filter($quotes, static fn ($q) => !!$q->role);

        if (empty($activeQuotes)) {
            return [new TextSegment($text)];
        }

        $segments = [];
        $cursor = 0;

        foreach ($activeQuotes as $match) {
            if ($match->position > $cursor) {
                $segments[] = new TextSegment(substr($text, $cursor, $match->position - $cursor));
            }

            // @phpstan-ignore-next-line argument.type – $match->role is never null at this point
            $segments[] = new QuoteSegment($match->role);

            $cursor = $match->position + 1;
        }

        if ($cursor < strlen($text)) {
            $segments[] = new TextSegment(substr($text, $cursor));
        }

        return $segments;
    }
}
