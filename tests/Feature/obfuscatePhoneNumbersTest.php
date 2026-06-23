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
    $result = obfuscatePhoneNumbers('+49 12 345 67');

    expect($result)->toBe('<!--html-processor:/ 345 67/+49 12/-->');
});
