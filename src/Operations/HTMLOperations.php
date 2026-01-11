<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Operations;

final class HTMLOperations
{
    /** @var array<string, HTMLOperation> */
    protected array $operations;

    public function add(
        HTMLOperation $operation
    ) {
        $this->operations[$operation->name] = $operation;
    }

    public function isEmpty(): bool
    {
        return empty($this->operations);
    }

    public function all(): array
    {
        return $this->operations;
    }
}
