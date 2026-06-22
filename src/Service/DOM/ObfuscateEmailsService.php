<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Dom\HTMLDocument;
use Dom\Text;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use Override;

/**
 * Obfuscate email addresses in HTML to protect them from spam bots.
 *
 * @see https://spencermortensen.com/articles/email-obfuscation/
 */
final readonly class ObfuscateEmailsService implements DOMServiceContract
{
    #[Override]
    public function prio(): int
    {
        return 10;
    }

    private const string EMAIL_REGEX = "[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}";

    #[Override]
    public function run(HTMLDocument $document): void
    {
        $this->obfuscateLinks($document);

        foreach (Support::getTextNodes($document) as $node) {
            $this->obfuscateTextNode($node, $document);
        }
    }

    /**
     * Convert <a href="mailto:...">
     *
     * @see https://spencermortensen.com/articles/email-obfuscation/#link-conversion
     */
    private function obfuscateLinks(HTMLDocument $document): void
    {
        foreach ($document->querySelectorAll('a[href*="mailto:"]') as $link) {
            $email = substr($link->getAttribute('href') ?? '', strlen('mailto:'));
            if (!preg_match('/^' . self::EMAIL_REGEX . '$/', $email)) {
                continue;
            }

            $link->removeAttribute('href');
            $link->setAttribute('data-html-processor', $this->encode($email));
        }
    }

    /**
     * Text-conversion: naked emails in text nodes
     *
     * @see https://spencermortensen.com/articles/email-obfuscation/#text-conversion
     */
    private function obfuscateTextNode(Text $node, HTMLDocument $document): void
    {
        if (!str_contains($node->data, '@')) {
            return;
        }

        $obfuscated = preg_replace_callback(
            pattern: '/' . self::EMAIL_REGEX . '/',
            callback: function ($matches) {
                $encoded = $this->encode($matches[0]);
                return <<<HTML
                <!--html-processor:$encoded-->
                HTML;
            },
            subject: $node->data
        ) ?? '';

        $parsed = Support::parseHtml("<body>$obfuscated</body>")?->firstChild;

        if ($parsed) {
            $node->replaceWith($document->importNode($parsed, deep: true));
        }
    }

    /**
     * Encode an email address by reversing the local part and domain separately,
     * joined by a slash: user@example.com → resu/moc.elpmaxe
     */
    private function encode(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        return strrev($local) . '/' . strrev($domain);
    }
}
