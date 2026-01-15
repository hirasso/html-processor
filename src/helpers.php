<?php

/*
 * Copyright (c) Rasso Hilber
 * https://rassohilber.com
 */

use Hirasso\HTMLProcessor\HTMLProcessor;

if (! function_exists('html')) {

    /**
     * Process a HTML string using a fluent API
     * @see https://github.com/hirasso/html-processor
     */
    function html(string $html): HTMLProcessor
    {
        return HTMLProcessor::fromString($html);
    }
}
