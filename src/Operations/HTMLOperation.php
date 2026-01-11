<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Operations;

use Closure;

final readonly class HTMLOperation
{
    /**
     * @param Closure(string): string $handler
     */
    public function __construct(
        public string $name,
        public Closure $handler,
    ) {
    }
}
