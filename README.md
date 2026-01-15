# hirasso/html-processor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hirasso/html-processor.svg)](https://packagist.org/packages/hirasso/html-processor)
[![Test Status](https://img.shields.io/github/actions/workflow/status/hirasso/html-processor/ci.yml?label=tests)](https://github.com/hirasso/html-processor/actions/workflows/ci.yml)
[![Code Coverage](https://img.shields.io/codecov/c/github/hirasso/html-processor)](https://app.codecov.io/gh/hirasso/html-processor)

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

## Usage Example

```php
use Hirasso\HTMLProcessor\HTMLProcessor;
use Hirasso\HTMLProcessor\Enum\LinkType;

echo HTMLProcessor::fromString($html)
    ->autolinkUrls() // wrap raw url strings in `<a>` tags
    ->autolinkPrefix('@', 'https://your-instance.social/@') // link @profileName to Mastodon
    ->autolinkPrefix('#', 'https://your-instance.social/tags') // link #hashTag to Mastodon
    ->removeEmptyElements('p') // remove empty paragraphs
    ->encodeEmails() // encode emails to confuse spam bots
    ->typography(
        Typography::make('de_DE') // optimize typography. currently supported: 'en', 'de', 'fr'
            ->localizeQuotes() // format quotes based on locale
            ->preventWidows() // prevent widows
    )
    ->processLinks(
        addClasses: true, // automatically add classes by type (mailto:, tel, internal, external, ...)
        function ($el, $type) { // apply a custom callback to all links
            if ($type === LinkType::External) {
                $el->setAttribute('target', '_blank');
            }
        },
    );

```

Browse the <a href="./tests/Feature">tests/Feature folder</a> for more usage examples.
