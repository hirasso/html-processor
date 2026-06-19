<?php

use function Hirasso\HTMLProcessor\process;

uses(PHPUnit\Framework\TestCase::class);

$html = '<p>Visit http://example.com</p>';

test('when() with true bool runs the then callback', function () use ($html) {
    $result = process($html)->when(true, fn ($p) => $p->autolinkUrls())->apply();
    expect($result)->toContain('<a');
});

test('when() with false bool skips the then callback', function () use ($html) {
    $result = process($html)->when(false, fn ($p) => $p->autolinkUrls())->apply();
    expect($result)->not->toContain('<a');
});

test('when() with false bool runs the else callback', function () use ($html) {
    $result = process($html)
        ->when(false, fn ($p) => $p->autolinkUrls(), fn ($p) => $p->stripTags())
        ->apply();
    expect($result)->not->toContain('<a');
    expect($result)->not->toContain('<p');
});

test('when() with closure condition evaluated immediately', function () use ($html) {
    $flag = true;
    $result = process($html)->when(fn () => $flag, fn ($p) => $p->autolinkUrls())->apply();
    expect($result)->toContain('<a');
});

test('when() closure condition receives the processor', function () use ($html) {
    $receivedProcessor = null;
    $processor = process($html);
    $processor->when(function ($p) use (&$receivedProcessor) {
        $receivedProcessor = $p;
        return false;
    }, fn ($p) => $p->autolinkUrls());
    expect($receivedProcessor)->toBe($processor);
});

test('when() is chainable', function () use ($html) {
    $result = process($html)
        ->when(false, fn ($p) => $p->autolinkUrls())
        ->when(true, fn ($p) => $p->stripTags())
        ->apply();
    expect($result)->not->toContain('<a');
    expect($result)->not->toContain('<p');
});
