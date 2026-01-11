<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\Contract;

interface HTMLServiceContract
{
    /**
     * Execute the HTML operation
     */
    public function run(string $html): string;

    /**
     * Get the unique name of this service
     */
    public function getName(): string;

    /**
     * Check if entities should be decoded after processing
     *
     * @return bool True if entities should be decoded (default), false otherwise
     */
    public function shouldDecodeEntities(): bool;
}
