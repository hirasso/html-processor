<?php

use Hirasso\HTMLProcessor\Support\Support;

function process(string $str): string {
    return Support::removeDuplicateIds($str);
}

test('Removes duplicate ID attributes', function () {
    $result = process('<div id="test">First</div><div id="test">Second</div>');

    expect($result)->toContain('id="test"');
    expect(substr_count($result, 'id="test"'))->toBe(1);
});

test('Keeps first occurrence of duplicate IDs', function () {
    $result = process('<span id="unique">First</span><strong id="unique">Second</strong>');

    expect($result)->toMatch('/<span id="unique">First<\/span>/');
    expect($result)->toMatch('/<strong>Second<\/strong>/');
});

test('Handles different quote styles', function () {
    $result = process('<div id="test">A</div><div id=\'test\'>B</div><div id=test>C</div>');
    // Only first occurrence should have id attribute
    expect(substr_count($result, 'id='))->toBe(1);
});

test('Handles spacing variations', function () {
    $result = process('<div id="test">A</div><div id = "test">B</div>');
    expect($result)->toContain('id="test"');
});

test('Preserves unique IDs', function () {
    $result = process('<div id="one">A</div><div id="two">B</div><div id="three">C</div>');

    expect($result)->toContain('id="one"');
    expect($result)->toContain('id="two"');
    expect($result)->toContain('id="three"');
});

test('ignores duplicate ids outside of tags', function () {
    // No DOM operations = runDOMQueue returns early
    $html = 'id="test" <span id="test"></span>';
    $result = process($html);
    // Duplicates remain (removeDuplicateIds never called)
    expect($result)->toBe($html);
});
