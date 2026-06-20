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

test('getTextNodes() ignores empty nodes by default', function () {
    $doc = Support::createDocument('<p>hello <span>world</span> <span> </span>!</p>');

    $nodes = Support::getTextNodes($doc, ignoreEmpty: false);
    expect(count($nodes))->toBe(5);

    $nodes = Support::getTextNodes($doc);
    expect(count($nodes))->toBe(3);
});
