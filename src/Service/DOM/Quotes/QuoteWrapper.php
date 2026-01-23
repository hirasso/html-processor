<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Quotes;

/**
 * Wraps quoted text in <q> HTML tags instead of replacing with typographic characters.
 *
 * Uses the same stack-based approach as QuoteReplacer for proper nesting:
 * Input:  "outer 'inner' outer"
 * Output: <q>outer <q>inner</q> outer</q>
 */
final class QuoteWrapper
{
    /**
     * Wrap quoted text with <q> tags
     */
    public function wrapQuotes(string $text): string
    {
        // Phase 1: Normalize existing curly quotes to standard ASCII quotes
        $text = $this->normalize($text, SingleQuote::all(), "'");
        $text = $this->normalize($text, DoubleQuote::all(), '"');

        // Phase 2: Apply stack-based wrapping
        return $this->wrapWithStack($text);
    }

    /**
     * Normalize curly quotes back to ASCII, but only if they look like quotes
     * (not preceded by letter OR not followed by letter).
     * This preserves apostrophes like "don't" which are surrounded by letters.
     *
     * @param string[] $quotes Curly quote characters to normalize
     */
    private function normalize(string $text, array $quotes, string $replace): string
    {
        $matchBefore = '(?<!\p{L})'; // negative lookbehind: not preceded by letter
        $matchAfter = '(?!\p{L})';   // negative lookahead: not followed by letter

        // Build character class like [''‚‹›]
        $class = '[' . preg_quote(implode('', $quotes), '/') . ']';

        // Match if NOT preceded by letter OR NOT followed by letter
        $pattern = "/(?:{$matchBefore}{$class}|{$class}{$matchAfter})/u";

        return preg_replace($pattern, $replace, $text) ?? $text;
    }

    /**
     * Wrap quotes using stack-based matching for proper nesting.
     */
    private function wrapWithStack(string $text): string
    {
        // Step 1: Find all quote positions with context
        $quotes = $this->findQuotes($text);

        // Step 2: Assign Open/Close roles using a stack
        $this->assignRoles($quotes);

        // Step 3: Build output string
        return $this->buildOutput($text, $quotes);
    }

    /**
     * Find all ASCII quotes in text with their context.
     *
     * @return QuoteMatch[]
     */
    private function findQuotes(string $text): array
    {
        $quotes = [];

        if (preg_match_all('/[\'"]/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as [$char, $pos]) {
                $position = (int) $pos;
                $type = $char === "'" ? QuoteType::Single : QuoteType::Double;
                $length = 1;

                // Check preceding character
                $precededByLetter = false;
                if ($position > 0) {
                    $before = mb_substr(substr($text, 0, $position), -1, 1);
                    $precededByLetter = preg_match('/\p{L}/u', $before) === 1;
                }

                // Check following character
                $followedByLetter = false;
                $afterPos = $position + $length;
                if ($afterPos < strlen($text)) {
                    $after = mb_substr(substr($text, $afterPos), 0, 1);
                    $followedByLetter = preg_match('/\p{L}/u', $after) === 1;
                }

                $quotes[] = new QuoteMatch(
                    position: $position,
                    length: $length,
                    type: $type,
                    canOpen: !$precededByLetter,
                    canClose: !$followedByLetter,
                );
            }
        }

        return $quotes;
    }

    /**
     * Assign Open/Close roles to quotes using stacks.
     * Unlike QuoteReplacer, we track actual quote references to handle unpaired quotes.
     *
     * @param QuoteMatch[] $quotes
     */
    private function assignRoles(array $quotes): void
    {
        /** @var QuoteMatch[] $singleStack */
        $singleStack = [];
        /** @var QuoteMatch[] $doubleStack */
        $doubleStack = [];

        foreach ($quotes as $match) {
            $isSingle = $match->type === QuoteType::Single;
            $stack = $isSingle ? $singleStack : $doubleStack;

            if ($match->canClose && count($stack) > 0) {
                $match->role = QuoteRole::Close;
                if ($isSingle) {
                    array_pop($singleStack);
                } else {
                    array_pop($doubleStack);
                }
            } elseif ($match->canOpen) {
                $match->role = QuoteRole::Open;
                if ($isSingle) {
                    $singleStack[] = $match;
                } else {
                    $doubleStack[] = $match;
                }
            }
        }

        // Unassign roles from unpaired opening quotes
        foreach ($singleStack as $unpaired) {
            $unpaired->role = null;
        }
        foreach ($doubleStack as $unpaired) {
            $unpaired->role = null;
        }
    }

    /**
     * Build output string with <q> tags around matched quote pairs.
     *
     * @param QuoteMatch[] $quotes
     */
    private function buildOutput(string $text, array $quotes): string
    {
        // Index quotes by position for fast lookup
        $quotesByPosition = [];
        foreach ($quotes as $match) {
            $quotesByPosition[$match->position] = $match;
        }

        $result = '';
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            if (isset($quotesByPosition[$i])) {
                $match = $quotesByPosition[$i];

                if ($match->role === QuoteRole::Open) {
                    // Skip the quote char, emit <q>
                    $result .= '<q>';
                } elseif ($match->role === QuoteRole::Close) {
                    // Emit </q>, skip the quote char
                    $result .= '</q>';
                } else {
                    // Unmatched quote, emit as-is
                    $result .= $text[$i];
                }
            } else {
                $result .= $text[$i];
            }
        }

        return $result;
    }
}
