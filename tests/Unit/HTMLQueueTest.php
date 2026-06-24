<?php

use Hirasso\HTMLProcessor\Queue\HTMLQueue;
use Hirasso\HTMLProcessor\Service\Contract\HTMLServiceContract;
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

test('add() sorts services by priority', function () {
    $queue = new HTMLQueue();

    $first = new class () implements HTMLServiceContract {
        public function run(string $html): string
        {
            return $html . 'A';
        }
        public function prio(): int
        {
            return 1;
        }
    };
    $second = new class () implements HTMLServiceContract {
        public function run(string $html): string
        {
            return $html . 'B';
        }
        public function prio(): int
        {
            return 2;
        }
    };

    $queue->add($second);
    $queue->add($first);

    expect($queue->applyTo(''))->toBe('AB');
});
