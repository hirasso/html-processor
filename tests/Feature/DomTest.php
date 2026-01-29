<?php

namespace Tests\DomTest;

use Hirasso\HTMLProcessor\Internal\Support\Support;

function process(string $html): string
{
    return Support::extractBodyHTML(Support::createDocument($html));
}

test('Preserves attributes', function () {
    $before = trimLines("
    <style>The quick brown fox jumps over the lazy dog</style>
    <script>The quick brown fox jumps over the lazy dog</script>
    <pre>The quick brown fox jumps over the lazy dog</pre>");

    $after = process($before);

    expect($after)->toBe($before);
});
