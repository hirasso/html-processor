<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Operations;

use Closure;
use IvoPetkov\HTML5DOMDocument;

final readonly class DOMOperation
{
    /**
     * @param Closure(HTML5DOMDocument): void $handler
     */
    public function __construct(
        public string $name,
        public Closure $handler,
    ) {
    }
}
