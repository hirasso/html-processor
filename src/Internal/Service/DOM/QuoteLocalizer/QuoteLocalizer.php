<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM\QuoteLocalizer;

use DOMXPath;
use Hirasso\HTMLProcessor\Internal\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Internal\Service\DOM\Typography;
use Hirasso\HTMLProcessor\Internal\Support\Support;
use IvoPetkov\HTML5DOMDocument;

/**
 * Localize single and double quotes to the correct format in various languages.
 *
 * @see QuoteStyle for supported languages
 */
final class QuoteLocalizer implements DOMServiceContract
{
    public function prio(): int
    {
        return 0;
    }

    public function __construct(private Typography $typography)
    {
    }

    /**
     * Run the normalizer
     */
    public function run(HTML5DOMDocument $document): void
    {
        /** get the language code late to support ->setLocale() */
        $lang = $this->typography->getLanguageCode();

        if (!$lang || !$replacer = QuoteStyle::fromLang($lang)?->replacer()) {
            return;
        }

        if (!$textNodes = (new DOMXPath($document))->query('//text()')) {
            return;
        }

        foreach ($textNodes as $textNode) {
            $nodeValue = $textNode->nodeValue ?? '';

            if (empty(trim($nodeValue))) {
                continue;
            }

            $text = Support::decode($nodeValue);

            $text = $replacer->applyTo($text);

            $textNode->nodeValue = Support::encode($text, usePlaceholders: true);
        }
    }
}
