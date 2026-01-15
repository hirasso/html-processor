<?php

/*
* Copyright (c) 2025 Rasso Hilber
* https://rassohilber.com
*/

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\Typography;

use Hirasso\HTMLProcessor\Queue\DOMQueue;
use Hirasso\HTMLProcessor\Service\Contract\DOMServiceContract;
use IvoPetkov\HTML5DOMDocument;

final class Typography implements DOMServiceContract
{
    private DOMQueue $queue;

    private string $locale = 'en_US';

    public function prio(): int
    {
        return 0;
    }

    private function __construct(
    ) {
        $this->queue = new DOMQueue();
    }

    public static function fromLocale(string $locale): self
    {
        $instance = new self();
        $instance->setLocale($locale);

        return $instance;
    }

    public function setLocale(string $locale): self
    {
        if (!preg_match('/^[a-z]{2}([_-]|$).*/', $locale)) {
            throw new \InvalidArgumentException(
                "Invalid locale format: '{$locale}'. Expected format: 'en', 'en_US' or 'en-US'"
            );
        }

        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getLanguageCode(): ?string
    {
        $separator = str_contains($this->locale, '_') ? '_' : '-';
        return explode($separator, $this->locale)[0] ?: null;
    }

    private function applyDefaults(): self
    {
        $this->queue->add(new QuoteLocalizer($this));
        $this->queue->add(new WidowPreventer());
        return $this;
    }

    public function localizeQuotes(): self
    {
        $this->queue->add(new QuoteLocalizer($this));
        return $this;
    }

    public function preventWidows(): self
    {
        $this->queue->add(new WidowPreventer());
        return $this;
    }

    public function run(HTML5DOMDocument $document): void
    {
        /**
         * Apply the default services if no service was registered
         */
        if ($this->queue->isEmpty()) {
            $this->applyDefaults();
        }

        $this->queue->runServices($document);
    }
}
