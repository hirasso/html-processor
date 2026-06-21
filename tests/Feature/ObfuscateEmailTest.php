<?php

use function Hirasso\HTMLProcessor\process;

function obfuscateEmail(string $string): string
{
    return process($string)->obfuscateEmail(email: true)->apply();
}
