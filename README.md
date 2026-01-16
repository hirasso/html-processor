# hirasso/html-processor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hirasso/html-processor.svg?color=F5D350)](https://packagist.org/packages/hirasso/html-processor)
[![Test Status](https://img.shields.io/github/actions/workflow/status/hirasso/html-processor/ci.yml?label=tests&color=F5D350)](https://github.com/hirasso/html-processor/actions/workflows/ci.yml)
[![Code Coverage](https://img.shields.io/codecov/c/github/hirasso/html-processor?color=F5D350)](https://app.codecov.io/gh/hirasso/html-processor)

**A tiny HTML processor written in PHP 🐘**

## Features

- Fluent API
- Understands HTML5
- Optimized for performance
- Extensively tested

## Installation

```shell
composer require hirasso/html-processor
```

## Minimal Example

```php
use function Hirasso\HTMLProcessor\process;

echo process($html)
    ->autolinkUrls() // wrap raw url strings in `<a>` tags
    ->removeEmptyElements() // remove empty paragraphs
    ->encodeEmails() // encode emails to confuse spam bots
    ->typography('de_DE') // fix typography based on locale
    ->processLinks(); // add classes based on link type
```

## Maximal Example

```php
use function Hirasso\HTMLProcessor\process;

echo process($html)
    ->autolinkUrls() // wrap raw url strings in `<a>` tags
    ->autolinkPrefix('@', 'https://your-instance.social/@') // link @profileName to Mastodon
    ->autolinkPrefix('#', 'https://your-instance.social/tags') // link #hashTag to Mastodon
    ->removeEmptyElements('p,div') // remove empty paragraphs
    ->encodeEmails() // encode emails to confuse spam bots
    ->typography('de_DE', fn ($typo) => $typo
        ->localizeQuotes() // format quotes based on locale
        ->preventWidows() // prevent widows
    )
    ->processLinks(function ($link, $defaultHandler) { // process links by callback
        if ($link->type->value === 'external') {
            $link->el->setAttribute('target', '_blank');
        }
        $defaultHandler(); // run the default handler
    });

```

Browse the <a href="./tests/Feature">tests/Feature folder</a> for more usage examples.
