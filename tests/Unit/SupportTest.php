<?php

use Dom\DocumentFragment;
use Dom\Text;
use Dom\XPath;
use Hirasso\HTMLProcessor\Support\Support;

test('parseTextNode() parses html and preserves leading and trailing whitespace', function () {
    $doc = Support::createDocument('<p>hello</p>');
    $xp = new XPath($doc);
    $node = $xp->query('//text()')->item(0);

    assert($node instanceof Text);

    $node->data = ' <span>hello</span> ';
    $parsed = Support::parseTextNode($node);

    expect($parsed instanceof DocumentFragment)->toBe(true);
    expect(count($parsed->childNodes ?? []))->toBe(3);
});

test('parseTextNode() preserves leading and trailing white space', function () {
    $doc = Support::createDocument('<p>hello</p>');
    $xp = new XPath($doc);
    $node = $xp->query('//text()')->item(0);

    assert($node instanceof Text);

    $node->data = ' <em>please</em> preserve the whitespace ';
    $parsed = Support::parseTextNode($node);

    assert($parsed instanceof DocumentFragment);

    expect($parsed->textContent)->toBe(" please preserve the whitespace ");
});

test('parseTextNode() skips whitespace-only html', function () {
    $doc = Support::createDocument('<p>hello</p>');
    $xp = new XPath($doc);
    $node = $xp->query('//text()')->item(0);

    if (!$node instanceof Dom\Text) {
        throw new \RuntimeException('Expected a Dom\Text node');
    }

    Support::parseTextNode($node);

    expect(Support::extractBodyHTML($doc))->toBe('<p>hello</p>');
});

test('getTextNodes() ignores empty nodes', function () {
    $doc = Support::createDocument('<p>hello <span>world</span> <span> </span><span> <!-- foo --></span>!</p>');

    $unfiltered = [...new XPath($doc)->query('//text()')];
    expect(count($unfiltered))->toBe(6);

    $filtered = Support::getTextNodes($doc);
    expect(count($filtered))->toBe(3);
});
