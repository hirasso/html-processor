<?php

use function Hirasso\HTMLProcessor\process;

test('Autolinks URLs', function() {
    $result = process("example.com")->autolinkUrls()->apply();
    expect($result)->toBe('<a href="http://example.com">example.com</a>');
});

test('Autolinks Emails', function () {
    $result = process("mail@example.com")->autolinkUrls()->apply();
    expect($result)->toBe('<a href="mailto:mail@example.com">mail@example.com</a>');
});

test('Truncates autolinked URLs', function () {
    $result = process("https://example.com/very-long-url-that-should-absolutely-be-truncated")->autolinkUrls()->apply();
    expect($result)->toBe('<a href="https://example.com/very-long-url-that-should-absolutely-be-truncated">example.com/very-long-url-that-s...</a>');
});
