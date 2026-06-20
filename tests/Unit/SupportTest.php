<?php

use Dom\Text;
use Dom\XPath;
use Hirasso\HTMLProcessor\Support\Support;

test('parseTextNodeValue() parses html and preserves leading and trailing whitespace', function () {
    $doc = Support::createDocument('<p>hello</p>');
    $xp = new XPath($doc);
    $node = $xp->query('//text()')->item(0);

    assert($node instanceof Text);

    $node->data = ' <span>hello</span> ';
    $parsed = Support::parseTextNodeValue($node);

    expect(count($parsed->childNodes ?? []))->toBe(3);
});

test('parseTextNodeValue() preserves leading and trailing white space', function () {
    $doc = Support::createDocument('<p>hello</p>');
    $xp = new XPath($doc);
    $node = $xp->query('//text()')->item(0);

    assert($node instanceof Text);

    $node->data = ' <em>please</em> preserve the whitespace ';
    $parsed = Support::parseTextNodeValue($node);

    expect($parsed->textContent ?? null)->toBe(" please preserve the whitespace ");
});

test('parseTextNodeValue() skips whitespace-only html', function () {
    $doc = Support::createDocument('<p>hello</p>');
    $xp = new XPath($doc);
    $node = $xp->query('//text()')->item(0);

    if (!$node instanceof Dom\Text) {
        throw new \RuntimeException('Expected a Dom\Text node');
    }

    Support::parseTextNodeValue($node);

    expect(Support::extractBodyHTML($doc))->toBe('<p>hello</p>');
});

test('elementContainsOnlyText() returns true when element has only text nodes', function () {
    $doc = Support::createDocument('<p>hello world</p>');
    $p = $doc->querySelector('p');
    assert($p !== null);
    expect(Support::elementContainsOnlyText($p))->toBe(true);
});

test('elementContainsOnlyText() returns false when element has non-text children', function () {
    $doc = Support::createDocument('<p>hello <span>world</span></p>');
    $p = $doc->querySelector('p');
    assert($p !== null);
    expect(Support::elementContainsOnlyText($p))->toBe(false);
});

test('getTextNodes() ignores empty nodes', function () {
    $doc = Support::createDocument('<p>hello <span>world</span> <span> </span><span> <!-- foo --></span>!</p>');

    $unfiltered = [...new XPath($doc)->query('//text()')];
    expect(count($unfiltered))->toBe(6);

    $filtered = Support::getTextNodes($doc);
    expect(count($filtered))->toBe(3);
});
