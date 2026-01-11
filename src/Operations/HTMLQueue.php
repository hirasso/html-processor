<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Operations;

use Hirasso\HTMLProcessor\Contracts\HTMLQueueContract;

final class HTMLQueue implements HTMLQueueContract
{
    /** @var array<string, HTMLOperation> */
    protected array $operations = [];

    public function add(HTMLOperation $operation): void {
        $this->operations[$operation->name] = $operation;
    }

    public function isEmpty(): bool
    {
        return empty($this->operations);
    }

    /** @return array<string, HTMLOperation> */
    public function all(): array
    {
        return $this->operations;
    }
}
