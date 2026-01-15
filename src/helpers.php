<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor;

/**
 * Process a HTML string using a fluent API
 * @see https://github.com/hirasso/html-processor
 */
function html(?string $html = null): HTMLProcessor
{
    return HTMLProcessor::fromString($html ?? '');
}
