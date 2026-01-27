<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteNormalizer;

/**
 * Wraps quoted text in <q> HTML tags instead of replacing with typographic characters.
 *
 * Uses stack-based matching for proper nesting:
 * Input:  "outer "inner" outer"
 * Output: [QuoteOpen, Text("outer "), QuoteOpen, Text("inner"), QuoteClose, Text(" outer"), QuoteClose]
 */
final class QuoteWrapper
{
    private QuoteFinder $finder;

    public function __construct()
    {
        $this->finder = new QuoteFinder();
    }

    /**
     * Wrap quoted text and return structured segments.
     *
     * @return Segment[]
     */
    public function wrapQuotes(string $text): array
    {
        // Phase 1: Reset existing curly quotes to standard ASCII "
        $text = $this->resetQuotes($text);

        // Phase 2: Apply stack-based wrapping
        return $this->wrapWithStack($text);
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
     * @return Segment[]
     */
    private function wrapWithStack(string $text): array
    {
        $quotes = $this->finder->find($text);
        $this->assignRoles($quotes);

        return $this->buildSegments($text, $quotes);
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
        $activeQuotes = array_filter($quotes, static fn ($q) => $q->role !== null);

        if ($activeQuotes === []) {
            return $text === '' ? [] : [new Segment(SegmentType::Text, $text)];
        }

        $segments = [];
        $cursor = 0;

        foreach ($activeQuotes as $match) {
            if ($match->position > $cursor) {
                $segments[] = new Segment(SegmentType::Text, substr($text, $cursor, $match->position - $cursor));
            }

            $segments[] = new Segment(
                $match->role === QuoteRole::Open ? SegmentType::QuoteOpen : SegmentType::QuoteClose
            );

            $cursor = $match->position + 1; // Quote is always 1 byte (ASCII ")
        }

        if ($cursor < strlen($text)) {
            $segments[] = new Segment(SegmentType::Text, substr($text, $cursor));
        }

        return $segments;
    }
}
