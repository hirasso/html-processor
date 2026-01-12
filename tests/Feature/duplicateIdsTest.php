<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

test('Removes duplicate ID attributes', function () {
    $html = '<div id="test">First</div><div id="test">Second</div>';
    $result = HTMLProcessor::fromString($html)->removeEmptyElements()->apply();

    expect($result)->toContain('id="test"');
    expect(substr_count($result, 'id="test"'))->toBe(1);
});

test('Keeps first occurrence of duplicate IDs', function () {
    $html = '<span id="unique">First</span><strong id="unique">Second</strong>';
    $result = HTMLProcessor::fromString($html)->processLinks()->apply();

    expect($result)->toMatch('/<span id="unique">First<\/span>/');
    expect($result)->toMatch('/<strong>Second<\/strong>/');
});

test('Handles different quote styles', function () {
    $html = '<div id="test">A</div><div id=\'test\'>B</div><div id=test>C</div>';
    $result = HTMLProcessor::fromString($html)->typography()->apply();

    // Only first occurrence should have id attribute
    expect(substr_count($result, 'id='))->toBe(1);
});

test('Handles spacing variations', function () {
    $html = '<div id="test">A</div><div id = "test">B</div>';
    $result = HTMLProcessor::fromString($html)->removeEmptyElements()->apply();

    expect($result)->toContain('id="test"');
});

test('Preserves unique IDs', function () {
    $html = '<div id="one">A</div><div id="two">B</div><div id="three">C</div>';
    $result = HTMLProcessor::fromString($html)->removeEmptyElements()->apply();

    expect($result)->toContain('id="one"');
    expect($result)->toContain('id="two"');
    expect($result)->toContain('id="three"');
});

test('No duplicate removal without DOM operations', function () {
    $html = '<div id="test">First</div><div id="test">Second</div>';
    // No DOM operations = runDOMQueue returns early
    $result = HTMLProcessor::fromString($html)->apply();

    // Duplicates remain (removeDuplicateIds never called)
    expect($result)->toBe($html);
});

test('ignores duplicate ids outside of tags', function () {
    $html = 'id="test" <span id="test"></span>';
    // No DOM operations = runDOMQueue returns early
    $result = HTMLProcessor::fromString($html)->removeEmptyElements()->apply();

    // Duplicates remain (removeDuplicateIds never called)
    expect($result)->toBe($html);
});
