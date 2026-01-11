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

## Usage

```php
use Hirasso\HTMLProcessor\HTMLProcessor;

echo HTMLProcessor::fromString($html)
    ->autolink() // wrap raw url strings in `<a>` tags
    ->localizeQuotes('de_DE') // localize quotes based on locale
    ->processLinks() // mark link types via class attribute (mailto:, tel:, internal, external, ...)
    ->beautify() // remove empty paragraphs, prevent widows
    ->linkToSocial('#', 'https://bsky.app/hashtag') // automatically link #hashtags to Bluesky
    ->encodeEmails(); // encode emails to confuse spam bots

```

Browse the <a href="./tests/Feature">tests/Feature folder</a> for more usage examples.