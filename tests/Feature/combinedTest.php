<?php

use Hirasso\HTMLProcessor\HTMLProcessor;

test('Runs various tasks on a string', function () {
    $html = 'Please reach out to mail@example.com to learn more about us, or follow @acme on instagram';

    $processor = HTMLProcessor::fromString($html)
        ->autolink()
        ->instagram()
        ->localizeQuotes('en_US')
        ->processLinks(fn ($el) => $el->setAttribute('data-my-attr', ''))
        ->beautify();

    expect($processor->toHTML())
        ->toBe('Please reach out to <a href="mailto:mail@example.com" class="link--mailto" data-my-attr="">mail@example.com</a>to learn more about us, or follow <a href=“https://www.instagram.com/acme”>@acme</a> on&nbsp;instagram');
});
