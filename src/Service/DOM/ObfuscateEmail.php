<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Dom\HTMLDocument;
use Dom\Text;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Service\Trait\HasDefaultPrio;
use Hirasso\HTMLProcessor\Support\Support;

/**
 * Obfuscate email addresses in HTML to protect them from spam bots.
 *
 * @see https://spencermortensen.com/articles/email-obfuscation/
 */
final readonly class ObfuscateEmail implements DOMServiceContract
{
    use HasDefaultPrio;

    private const string EMAIL_REGEX = "[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}";

    public function __construct(
        private bool $email = true,
    ) {
    }

    public function run(HTMLDocument $document): void
    {
        if (!$this->email) {
            return;
        }

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
        foreach ($document->querySelectorAll('a[href^="mailto:"]') as $link) {
            $email = substr($link->getAttribute('href') ?? '', 7);
            if (!preg_match('/^' . self::EMAIL_REGEX . '$/', $email)) {
                continue;
            }

            $link->setAttribute('href', $this->encode($email));
            $link->setAttribute('rel', 'nofollow noindex');
            $script = $document->createElement('script');
            $script->textContent = <<<'JS'
            document.currentScript.closest('a').setAttribute('href', 'mailto:' + document.currentScript.closest('a').getAttribute('href').split('/').map(function(p){return p.split('').reverse().join('');}).join('@'));
            document.currentScript.remove();
            JS;
            $link->append($script);
        }
    }

    /**
     * Text-conversion: naked email in text nodes → inline JS
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
                <body><script>document.currentScript.replaceWith('$encoded'.split('/').map(function(p){return p.split('').reverse().join('');}).join('@'))</script></body>
                HTML;
            },
            subject: $node->data
        ) ?? '';

        if ($parsed = Support::parseHtml($obfuscated)) {
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
