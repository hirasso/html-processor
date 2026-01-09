<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

test('Encodes Email addresses', function () {
    $result = HTMLProcessor::fromString('<a href="mailto:mail@example.com">mail@example.com</a>')->encodeEmails();
    expect($result)->not()->toBe('<a href="mailto:mail@example.com">mail@example.com</a>');
});
