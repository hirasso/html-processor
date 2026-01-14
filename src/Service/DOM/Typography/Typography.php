<?php

/*
* Copyright (c) 2025 Rasso Hilber
* https://rassohilber.com
*/

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Typography;

use Hirasso\HTMLProcessor\Queue\DOMQueue;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use Hirasso\HTMLProcessor\Service\DOM\Typography\QuoteLocalizer;
use Hirasso\HTMLProcessor\Service\DOM\Typography\WidowPreventer;
use IvoPetkov\HTML5DOMDocument;

final class Typography implements DOMServiceContract
{
    private DOMQueue $queue;

    public function prio(): int {
        return 0;
    }

    private function __construct(
        private string $locale
    ) {
        $this->queue = new DOMQueue();
    }

    public static function make(?string $locale = null): self {
        return new self($locale ?? 'en_US');
    }

    public function applyDefaults(): self {
        $this->queue->add(new QuoteLocalizer($this->locale));
        $this->queue->add(new WidowPreventer());
        return $this;
    }

    public function localizeQuotes(): self
    {
        $this->queue->add(new QuoteLocalizer($this->locale));
        return $this;
    }

    public function preventWidows(): self
    {
        $this->queue->add(new WidowPreventer());
        return $this;
    }

    public function run(HTML5DOMDocument $document): void {
        /**
         * Apply the default services if no service was registered
         */
        if ($this->queue->isEmpty()) {
            $this->applyDefaults();
        }
        $this->queue->runServices($document);
    }
}
