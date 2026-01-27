<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Quotes;

/**
 * Wraps quoted text in <q> HTML tags instead of replacing with typographic characters.
 *
 * Uses the same stack-based approach as QuoteReplacer for proper nesting:
 * Input:  "outer 'inner' outer"
 * Output: [QuoteOpenSegment, TextSegment("outer "), QuoteOpenSegment, TextSegment("inner"), QuoteCloseSegment, TextSegment(" outer"), QuoteCloseSegment]
 */
final class QuoteWrapper
{
    private QuoteFinder $finder;

    public function __construct()
    {
        $this->finder = new QuoteFinder();
    }
    /**
     * Wrap quoted text and return structured segments
     *
     * @return Segment[]
     */
    public function wrapQuotes(string $text): array
    {
        // Phase 1: Reset existing curly quotes to standard ASCII quotes
        $text = $this->resetQuotes($text, '"');

        // Phase 2: Apply stack-based wrapping
        return $this->wrapWithStack($text);
    }

    /**
     * Reset curly quotes back to ASCII, but only if they look like quotes
     * (not preceded by letter OR not followed by letter).
     * This preserves apostrophes like "don't" which are surrounded by letters.
     */
    private function resetQuotes(string $text, string $replace): string
    {
        $matchBefore = '(?<!\p{L})'; // negative lookbehind: not preceded by letter
        $matchAfter = '(?!\p{L})';   // negative lookahead: not followed by letter

        $quotes = [...SingleQuote::all(), ...DoubleQuote::all()];

        // Build character class like [''‚‹›]
        $class = '[' . preg_quote(implode('', $quotes), '/') . ']';

        // Match if NOT preceded by letter OR NOT followed by letter
        $pattern = "/(?:{$matchBefore}{$class}|{$class}{$matchAfter})/u";

        return preg_replace($pattern, $replace, $text) ?? $text;
    }

    /**
     * Wrap quotes using stack-based matching for proper nesting.
     *
     * @return Segment[]
     */
    private function wrapWithStack(string $text): array
    {
        // Step 1: Find all quote positions with context
        $quotes = $this->finder->find($text, '/"/');

        // Step 2: Assign Open/Close roles using a stack
        $this->assignRoles($quotes);

        // Step 3: Build output segments
        return $this->buildSegments($text, $quotes);
    }

    /**
     * Assign Open/Close roles to quotes using a stack.
     * Tracks actual quote references to handle unpaired quotes.
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

        // Unassign roles from unpaired opening quotes
        foreach ($stack as $unpaired) {
            $unpaired->role = null;
        }
    }

    /**
     * Build output segments from text and quote matches.
     *
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
            // Text before this quote
            if ($match->position > $cursor) {
                $segments[] = new Segment(SegmentType::Text, substr($text, $cursor, $match->position - $cursor));
            }

            // Quote marker
            $segments[] = new Segment(
                $match->role === QuoteRole::Open ? SegmentType::QuoteOpen : SegmentType::QuoteClose
            );

            $cursor = $match->position + $match->length;
        }

        // Remaining text
        if ($cursor < strlen($text)) {
            $segments[] = new Segment(SegmentType::Text, substr($text, $cursor));
        }

        return $segments;
    }
}
