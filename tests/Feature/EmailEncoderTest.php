<?php

use function Hirasso\HTMLProcessor\process;

function encode(string $string): string
{
    return process($string)->encodeEmails()->apply();
}
function expectEncoded(string $string): void
{
    expect(str_starts_with($string, '<a href="'))->toBe(true);
    expect(str_ends_with($string, '</a>'))->toBe(true);
    expect(substr_count($string, '#') > 10)->toBe(true);
}

test('Encodes Email addresses', function () {
    expectEncoded(encode('<a href="mailto:mail@example.com">mail@example.com</a>'));
});
