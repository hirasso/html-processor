<?php

use Hirasso\HTMLProcessor\Queue\HTMLQueue;
use Hirasso\HTMLProcessor\Service\HTML\ObfuscateContacts;
use Hirasso\HTMLProcessor\Service\HTML\StripTags;

test('isEmpty() returns true when empty', function () {
    $queue = new HTMLQueue();
    expect($queue->isEmpty())->toBeTrue();
});

test('isEmpty() returns false after adding a service', function () {
    $queue = new HTMLQueue();
    $queue->add(new StripTags(null));
    expect($queue->isEmpty())->toBeFalse();
});

test('get() returns null for missing service', function () {
    $queue = new HTMLQueue();
    expect($queue->get(StripTags::class))->toBeNull();
});

test('get() returns service by class', function () {
    $queue = new HTMLQueue();
    $service = new StripTags(null);
    $queue->add($service);
    expect($queue->get(StripTags::class))->toBe($service);
});

test('add() invokes sort when multiple services are present', function () {
    $queue = new HTMLQueue();
    $queue->add(new StripTags(null));
    $queue->add(new ObfuscateContacts());
    expect($queue->isEmpty())->toBeFalse();
});

test('applyTo() with filter only runs matching services', function () {
    $queue = new HTMLQueue();
    $queue->add(new StripTags(null));
    $queue->add(new ObfuscateContacts());

    $result = $queue->applyTo(
        '<p><a href="mailto:mail@example.com">example</a></p>',
        fn ($s) => $s instanceof StripTags
    );

    // StripTags ran (stripped tags), ObfuscateContacts did not (email not encoded)
    expect($result)->toBe('example');
    expect($result)->not->toContain('&#');
});
