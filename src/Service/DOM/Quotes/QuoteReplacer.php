<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Quotes;

/**
 * Replaces standard ASCII quotes with localized typographic quotes.
 *
 * Uses a two-phase approach:
 * 1. Normalize: Convert any existing curly quotes back to ASCII (' and ")
 * 2. Localize: Use stack-based matching to assign open/close roles, then replace
 *
 * The stack-based approach handles nested same-type quotes correctly:
 * Input:  "foo "outer 'inner' outer" bar"
 * Output: "foo "outer 'inner' outer" bar"  (L L L R R R pattern)
 */
final readonly class QuoteReplacer
{
    public function __construct(
        public string $lang,
        public QuotePair $single,
        public QuotePair $double
    ) {
    }

    /**
     * Apply this replacer to a string
     */
    public function applyTo(string $text): string
    {
        // Phase 1: Normalize existing curly quotes to standard ASCII quotes
        $text = $this->normalize($text, SingleQuote::all(), "'");
        $text = $this->normalize($text, DoubleQuote::all(), '"');

        // Phase 2: Apply stack-based localization
        $text = $this->localizeWithStack($text);

        return $text;
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
        // This catches opening quotes (space before) and closing quotes (space after)
        $pattern = "/(?:{$matchBefore}{$class}|{$class}{$matchAfter})/u";

        return preg_replace($pattern, $replace, $text) ?? $text;
    }

    /**
     * Localize quotes using stack-based matching for proper nesting.
     *
     * Why stack-based? A greedy regex like "(.*)" matches from first to last quote,
     * breaking nested structures. The stack tracks unclosed quotes, enabling correct
     * pairing even with nesting: "foo "bar" baz" → L L R R (not L-----R)
     */
    private function localizeWithStack(string $text): string
    {
        // Step 1: Find all quote positions with context (can open? can close?)
        $quotes = $this->findQuotes($text);

        // Step 2: Walk through quotes and assign Open/Close roles using a stack
        $this->assignRoles($quotes);

        // Step 3: Replace from end to start to preserve byte positions
        // (replacing from start would shift positions of subsequent quotes)
        for ($i = count($quotes) - 1; $i >= 0; $i--) {
            $match = $quotes[$i];
            if ($match->role === null) {
                continue; // Unmatched quote, leave as-is
            }

            $pair = $match->type === QuoteType::Single ? $this->single : $this->double;
            $replacement = $match->role === QuoteRole::Open ? $pair->opening : $pair->closing;

            $text = substr_replace($text, $replacement, $match->position, $match->length);
        }

        return $text;
    }

    /**
     * Assign Open/Close roles to quotes using stack counters.
     *
     * Algorithm:
     * - Walk left to right through all quotes
     * - If quote can close AND there's an unclosed quote of same type → Close (pop)
     * - Else if quote can open → Open (push)
     * - Else → unmatched, role stays null
     *
     * @param QuoteMatch[] $quotes
     */
    private function assignRoles(array $quotes): void
    {
        $singleStack = 0;
        $doubleStack = 0;

        foreach ($quotes as $match) {
            $isSingle = $match->type === QuoteType::Single;
            $stack = $isSingle ? $singleStack : $doubleStack;

            if ($match->canClose && $stack > 0) {
                $match->role = QuoteRole::Close;
                if ($isSingle) {
                    $singleStack--;
                } else {
                    $doubleStack--;
                }
            } elseif ($match->canOpen) {
                $match->role = QuoteRole::Open;
                if ($isSingle) {
                    $singleStack++;
                } else {
                    $doubleStack++;
                }
            }
        }
    }

    /**
     * Find all ASCII quotes in text with their context.
     *
     * Context determines if a quote can be opening or closing:
     * - canOpen: NOT preceded by a letter (e.g., space/start before quote)
     * - canClose: NOT followed by a letter (e.g., space/punctuation/end after)
     *
     * Examples:
     * - "Hello"  → both quotes can open AND close (space context)
     * - don't    → apostrophe can't open (d before) AND can't close (t after) → ignored
     * - Edit's   → apostrophe can't open (t before) AND can't close (s after) → ignored
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
                $length = 1; // ASCII quotes are always 1 byte

                // Check preceding character (Unicode-aware via \p{L})
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
}
