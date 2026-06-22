<?php

use function Hirasso\HTMLProcessor\process;

function obfuscate(string $string): string
{
    return process($string)->obfuscate()->apply();
}
