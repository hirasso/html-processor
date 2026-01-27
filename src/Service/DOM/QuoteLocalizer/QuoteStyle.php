<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\QuoteLocalizer;

/**
 * Localized quote styles by language.
 *
 * To add a new language:
 * 1. Add a new case with the language code as value
 * 2. Add the corresponding match arm in replacer()
 *
 * @see https://en.wikipedia.org/wiki/Quotation_mark
 */
enum QuoteStyle: string
{
    case English = 'en';
    case German = 'de';
    case French = 'fr';
    case Spanish = 'es';

    /**
     * Get the QuoteReplacer for this style.
     */
    public function replacer(): QuoteReplacer
    {
        return match ($this) {
            self::English => new QuoteReplacer(
                lang: $this->value,
                single: new QuotePair("\u{2018}", "\u{2019}"), // ' '
                double: new QuotePair("\u{201C}", "\u{201D}"), // " "
            ),
            self::German => new QuoteReplacer(
                lang: $this->value,
                single: new QuotePair("\u{201A}", "\u{2018}"), // ‚ '
                double: new QuotePair("\u{201E}", "\u{201C}"), // „ "
            ),
            self::French => new QuoteReplacer(
                lang: $this->value,
                single: new QuotePair("\u{2039}\u{202F}", "\u{202F}\u{203A}"), // ‹ ›
                double: new QuotePair("\u{00AB}\u{202F}", "\u{202F}\u{00BB}"), // « »
            ),
            self::Spanish => new QuoteReplacer(
                lang: $this->value,
                single: new QuotePair("\u{201E}", "\u{201C}"), // „ "
                double: new QuotePair("\u{00AB}", "\u{00BB}"), // « »
            ),
        };
    }

    /**
     * Get a QuoteStyle by language code, or null if unsupported.
     */
    public static function fromLang(string $lang): ?self
    {
        return self::tryFrom($lang);
    }
}
