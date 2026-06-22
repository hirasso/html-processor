<?php

use function Hirasso\HTMLProcessor\process;

function obfuscate(string $string): string
{
    return process($string)->obfuscate()->apply();
}

test('Obfuscates emails in links', function () {
    $result = obfuscate('<a href="mailto:mail@example.com">email</a>');

    expect($result)->toContain('<script>');
    expect($result)->not->toContain('mail@example.com');
    expect($result)->toContain('closest("a").getAttribute("href").split("/").map((p)=>p.split("").reverse().join(""))');
});

test('Obfuscates emails in plaintext', function () {
    $result = obfuscate('mail@example.com');

    expect($result)->toContain('<script>');
    expect($result)->not->toContain('mail@example.com');
    expect($result)->toContain('replaceWith("liam/moc.elpmaxe".split("/").map((p)=>p.split("").reverse().join("")).join("@")');
});
