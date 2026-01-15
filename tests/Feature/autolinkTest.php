<?php

use function Hirasso\HTMLProcessor\html;

test('Autolinks URLS', function () {
    $result = html("mail@example.com")->autolinkUrls()->apply();
    expect($result)->toBe('<a href="mailto:mail@example.com">mail@example.com</a>');

    $result = html("example.com")->autolinkUrls()->apply();
    expect($result)->toBe('<a href="http://example.com">example.com</a>');
});

test('Truncates autolinked URLs', function () {
    $result = html("https://example.com/very-long-url-that-should-absolutely-be-truncated")->autolinkUrls()->apply();
    expect($result)->toBe('<a href="https://example.com/very-long-url-that-should-absolutely-be-truncated">example.com/very-long-url-that-s...</a>');
});
