<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

use Closure;
use IvoPetkov\HTML5DOMDocument;

enum OperationType
{
    case DOM;
    case HTML;
}

final readonly class Operation
{
    /**
     * @param Closure(HTML5DOMDocument): void|Closure(string): string $handler
     */
    public function __construct(
        public OperationType $type,
        public string $name,
        public Closure $handler,
    ) {
    }
}
