<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

test('Autolinks URLS', function () {
    $result = HTMLProcessor::fromString("mail@example.com")->autolinkUrls()->apply();
    expect($result)->toBe('<a href="mailto:mail@example.com">mail@example.com</a>');

    $result = HTMLProcessor::fromString("example.com")->autolinkUrls()->apply();
    expect($result)->toBe('<a href="http://example.com">example.com</a>');
});

test('Truncates autolinked URLs', function () {
    $result = HTMLProcessor::fromString("https://example.com/very-long-url-that-should-absolutely-be-truncated")->autolinkUrls()->apply();
    expect($result)->toBe('<a href="https://example.com/very-long-url-that-should-absolutely-be-truncated">example.com/very-long-url-that-s...</a>');
});
