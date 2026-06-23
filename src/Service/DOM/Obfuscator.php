<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Dom\Element;
use Dom\HTMLDocument;
use Dom\Text;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Support\Support;
use Override;
use RuntimeException;
use Closure;

/**
 * Obfuscate emails and phone numbers to protect them from spam bots
 *
 * @see https://spencermortensen.com/articles/email-obfuscation/
 */
final class Obfuscator implements DOMServiceContract
{
    #[Override]
    public function prio(): int
    {
        return 10;
    }

    private const string EMAIL_REGEX = "[^\s@]+@[^\s@]+\.[^\s@]{2,}";
    private const string PHONE_NUMBER_REGEX = "[\+\d][\d \-\(\)\.]{6,20}(?<!\s)";

    public static bool $injected = false;

    private string $passphrase = 'html-processor';
    private bool $obfuscateEmails = true;
    private bool $obfuscatePhoneNumbers = true;
    private bool $injectJS = true;

    /**
     * @param ?Closure(self $obfuscator): self $userCallback
     */
    public function __construct(private ?Closure $userCallback = null)
    {
    }

    /**
     * Should email addresses be obfuscated?
     */
    public function obfuscateEmails(bool $bool = true): self
    {
        $this->obfuscateEmails = $bool;
        return $this;
    }

    /**
     * Should phone numbers be obfuscated?
     */
    public function obfuscatePhoneNumbers(bool $bool = true): self
    {
        $this->obfuscatePhoneNumbers = $bool;
        return $this;
    }

    /**
     * Set a custom passphrase for improved security
     */
    public function setPassphrase(string $passphrase): self
    {
        $this->passphrase = $passphrase;
        return $this;
    }

    private function getKey(): string
    {
        return md5($this->passphrase . rand(0, 100));
    }

    public function injectDeobfuscationScript(bool $bool): self
    {
        $this->injectJS = $bool;
        return $this;
    }

    #[Override]
    public function run(HTMLDocument $document): void
    {
        /** Apply the user callback if provided */
        if ($this->userCallback !== null) {
            ($this->userCallback)($this);
        }

        $this->maybeInjectJS($document);
        $this->obfuscateLinks($document);

        if ($this->obfuscateEmails) {
            foreach (Support::getTextNodes($document) as $node) {
                $this->obfuscateTextNode($node, self::EMAIL_REGEX);
            }
        }
        if ($this->obfuscatePhoneNumbers) {
            foreach (Support::getTextNodes($document) as $node) {
                $this->obfuscateTextNode($node, self::PHONE_NUMBER_REGEX);
            }
        }
    }

    /**
     * Obfuscate links
     */
    private function obfuscateLinks(HTMLDocument $document): void
    {
        if ($this->obfuscateEmails) {
            foreach ($document->querySelectorAll('a[href*="mailto:"]') as $link) {
                $email = substr($link->getAttribute('href') ?? '', strlen('mailto:'));
                if (!preg_match('/^' . self::EMAIL_REGEX . '$/', $email)) {
                    continue;
                }
                $link->replaceWith($this->obfuscateElement($link));
            }
        }
        if ($this->obfuscatePhoneNumbers) {
            foreach ($document->querySelectorAll('a[href*="tel:"]') as $link) {
                $tel = substr($link->getAttribute('href') ?? '', strlen('tel:'));
                if (!preg_match('/^' . self::PHONE_NUMBER_REGEX . '$/', $tel)) {
                    continue;
                }
                $link->replaceWith($this->obfuscateElement($link));
            }
        }
    }

    /**
     * Process a whole element
     */
    private function obfuscateElement(Element $el): Element
    {
        if (!$el->ownerDocument) {
            throw new RuntimeException('No owner document found');
        }
        $key = $this->getKey();

        $obfuscated = $el->ownerDocument->createElement('html-processor-obfuscated');
        $obfuscated->setAttribute('value', $this->encode(Support::outerHTML($el), $key));
        $obfuscated->setAttribute('key', $key);
        $obfuscated->setAttribute('type', 'element');

        return $obfuscated;
    }

    /**
     * Obfuscate a text node
     */
    private function obfuscateTextNode(Text $node, string $regex): void
    {
        $obfuscated = preg_replace_callback(
            "/{$regex}/",
            fn ($matches) => $this->obfuscateString($matches[0]),
            $node->data
        ) ?? $node->data;

        if ($obfuscated === $node->data) {
            return;
        }

        $node->data = $obfuscated;
        Support::hydrateTextNode($node);
    }

    /**
     * Obfuscate a string
     */
    private function obfuscateString(string $value): string
    {
        $key = $this->getKey();
        $encoded = $this->encode($value, $key);

        return sprintf(
            <<<HTML
            <html-processor-obfuscated value="%s" key="%s"></html-processor-obfuscated>
            HTML,
            $encoded,
            $key
        );
    }

    /**
     * Encode a string, using a passphrase key
     */
    private function encode(string $data, string $key): string
    {
        $out = '';
        for ($i = 0; $i < mb_strlen($data); $i++) {
            $out .= mb_substr($data, $i, 1) ^ mb_substr($key, $i % mb_strlen($key), 1);
        }
        return base64_encode($out);
    }

    /**
     * Inject the script that de-obfuscates obfuscated emails in the frontend.
     * This intentionally runs only ONCE per PHP process, since we only need it once
     */
    private function maybeInjectJS(HTMLDocument $document): void
    {
        if (self::$injected || !$this->injectJS) {
            return;
        }
        self::$injected = true;

        $script = $document->createElement('script');
        $script->setAttribute('type', 'module');
        $script->textContent = file_get_contents(dirname(__DIR__, 3). '/resources/obfuscation.js') ?: '';
        $document->body?->append($script);
    }
}
