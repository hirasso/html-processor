<?php

use Dom\XPath;
use Hirasso\HTMLProcessor\Support\Support;

test('replaceTextNodeWithHtml skips whitespace-only html', function () {
    $doc = Support::createDocument('<p>hello</p>');
    $xp = new XPath($doc);
    $node = $xp->query('//text()')->item(0);

    if (!$node instanceof Dom\Text) {
        throw new \RuntimeException('Expected a Dom\Text node');
    }

    Support::replaceTextNodeWithHtml($node, '   ');

    expect(Support::extractBodyHTML($doc))->toBe('<p>hello</p>');
});
