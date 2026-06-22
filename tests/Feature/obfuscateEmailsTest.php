<?php

use function Hirasso\HTMLProcessor\process;

function obfuscate(string $string): string
{
    return process($string)->obfuscateEmails()->apply();
}

test('Obfuscates emails in links', function () {
    $result = obfuscate('<a href="mailto:mail@example.com">email</a>');

    expect($result)->toBe('<a data-html-processor="liam/moc.elpmaxe">email</a>');
});

test('Obfuscates emails in plaintext', function () {
    $result = obfuscate('mail@example.com');

    expect($result)->toBe('<!--html-processor:liam/moc.elpmaxe-->');
});
