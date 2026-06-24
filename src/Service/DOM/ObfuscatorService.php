<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM;

use Dom\HTMLDocument;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Override;
use Closure;
use Hirasso\HTMLObfuscator\HTMLObfuscator;

/**
 * Obfuscate emails and phone numbers to protect them from spam bots
 *
 * @see https://spencermortensen.com/articles/email-obfuscation/
 */
final class ObfuscatorService implements DOMServiceContract
{
    #[Override]
    public function prio(): int
    {
        return 10;
    }

    /**
     * @param ?Closure(HTMLObfuscator $obfuscator): mixed $userCallback
     */
    public function __construct(private ?Closure $userCallback = null)
    {

    }

    #[Override]
    public function run(HTMLDocument $document): HTMLDocument
    {
        $obfuscator = HTMLObfuscator::createFromDocument($document);

        /** Apply the user callback if provided */
        if ($this->userCallback !== null) {
            ($this->userCallback)($obfuscator);
        }

        return $obfuscator->apply()->getDocument();
    }
}
