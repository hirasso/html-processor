<?php

use Hirasso\HTMLProcessor\Queue\HTMLQueue;
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
