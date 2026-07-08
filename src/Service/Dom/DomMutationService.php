<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\Dom;

use Dom\HTMLDocument;
use Hirasso\HTMLProcessor\Service\Contract\DomServiceContract;
use Override;
use Closure;

/**
 * Obfuscate emails and phone numbers to protect them from spam bots
 *
 * @see https://spencermortensen.com/articles/email-obfuscation/
 */
final class DomMutationService implements DomServiceContract
{
    /**
     * @param Closure(HTMLDocument $document): mixed $mutation
     */
    public function __construct(private Closure $mutation, private int $prio = 0)
    {
    }

    #[Override]
    public function prio(): int
    {
        return $this->prio;
    }

    #[Override]
    public function run(HTMLDocument $document): void
    {
        ($this->mutation)($document);
    }
}
