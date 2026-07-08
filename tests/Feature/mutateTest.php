<?php

use function Hirasso\HTMLProcessor\process;

test('Mutates the document', function () {
    $result = process('<h1>Hello</h1>')
        ->mutate(fn (\Dom\HTMLDocument $doc) => $doc->querySelector('h1')?->setAttribute('class', 'text-2xl'))
        ->apply();

    expect($result)->toBe('<h1 class="text-2xl">Hello</h1>');
});
