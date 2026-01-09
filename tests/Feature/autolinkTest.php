<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

test('Autolinks URLS', function () {
    $result = HTMLProcessor::fromString("mail@example.com")->autolink()->toHTML();
    expect($result)->toBe('<a href="mailto:mail@example.com">mail@example.com</a>');

    $result = HTMLProcessor::fromString("example.com")->autolink()->toHTML();
    expect($result)->toBe('<a href="http://example.com">example.com</a>');
});
