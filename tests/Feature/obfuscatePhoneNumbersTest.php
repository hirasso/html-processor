<?php

use function Hirasso\HTMLProcessor\process;

function obfuscatePhoneNumbers(string $string): string
{
    return process($string)->obfuscatePhoneNumbers()->apply();
}

test('Obfuscates phone numbers in links', function () {
    $result = obfuscatePhoneNumbers('<a href="tel:+49 12 345 67">call us</a>');

    expect($result)->toBe('<a data-html-processor="/ 345 67/+49 12/">call us</a>');
});

test('Obfuscates phone numbers in plaintext', function () {
    $result = obfuscatePhoneNumbers('foo bar baz +49 12 345 67 foo bar baz');

    expect($result)->toBe('foo bar baz <!--html-processor:/345 67 /+49 12 /--> foo bar baz');
});
