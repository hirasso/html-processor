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
        $quotes = $this->findQuotes($text);

        // Step 2: Assign Open/Close roles using a stack
        $this->assignRoles($quotes);

        // Step 3: Build output segments
        return $this->buildSegments($text, $quotes);
    }

    /**
     * Find all quotes in text with their context.
     * All quotes have been normalized to " by resetQuotes().
     *
     * @return QuoteMatch[]
     */
    private function findQuotes(string $text): array
    {
        $quotes = [];

        if (preg_match_all('/"/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as [, $pos]) {
                $position = (int) $pos;

                // Check preceding character
                $precededByLetter = false;
                if ($position > 0) {
                    $before = mb_substr(substr($text, 0, $position), -1, 1);
                    $precededByLetter = preg_match('/\p{L}/u', $before) === 1;
                }

                // Check following character
                $followedByLetter = false;
                if ($position + 1 < strlen($text)) {
                    $after = mb_substr(substr($text, $position + 1), 0, 1);
                    $followedByLetter = preg_match('/\p{L}/u', $after) === 1;
                }

                $quotes[] = new QuoteMatch(
                    position: $position,
                    length: 1,
                    type: QuoteType::Double,
                    canOpen: !$precededByLetter,
                    canClose: !$followedByLetter,
                );
            }
        }

        return $quotes;
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
        // Index quotes by position for fast lookup
        $quotesByPosition = [];
        foreach ($quotes as $match) {
            $quotesByPosition[$match->position] = $match;
        }

        $segments = [];
        $currentText = '';
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            if (isset($quotesByPosition[$i])) {
                $match = $quotesByPosition[$i];

                if ($match->role === QuoteRole::Open) {
                    // Flush accumulated text
                    if ($currentText !== '') {
                        $segments[] = new Segment(SegmentType::Text, $currentText);
                        $currentText = '';
                    }
                    $segments[] = new Segment(SegmentType::QuoteOpen);
                } elseif ($match->role === QuoteRole::Close) {
                    // Flush accumulated text
                    if ($currentText !== '') {
                        $segments[] = new Segment(SegmentType::Text, $currentText);
                        $currentText = '';
                    }
                    $segments[] = new Segment(SegmentType::QuoteClose);
                } else {
                    // Unmatched quote, keep as text
                    $currentText .= $text[$i];
                }
            } else {
                $currentText .= $text[$i];
            }
        }

        // Flush remaining text
        if ($currentText !== '') {
            $segments[] = new Segment(SegmentType::Text, $currentText);
        }

        return $segments;
    }
}
