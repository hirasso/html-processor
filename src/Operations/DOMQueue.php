<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Operations;

use Hirasso\HTMLProcessor\Contracts\DOMQueueContract;

final class DOMQueue implements DOMQueueContract
{
    /** @var array<string, DOMOperation> */
    protected array $operations = [];

    public function add(DOMOperation $operation): void {
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
