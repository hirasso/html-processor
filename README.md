# hirasso/html-processor

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hirasso/html-processor.svg)](https://packagist.org/packages/hirasso/html-processor)
[![Test Status](https://img.shields.io/github/actions/workflow/status/hirasso/html-processor/ci.yml?label=tests)](https://github.com/hirasso/html-processor/actions/workflows/ci.yml)
[![Code Coverage](https://img.shields.io/codecov/c/github/hirasso/html-processor)](https://app.codecov.io/gh/hirasso/html-processor)

**A tiny HTML processor written in PHP 🐘**

## Installation

```shell
composer require hirasso/html-processor
```

## Usage

```php
use Hirasso\HTMLProcessor\HTMLProcessor;

echo HTMLProcessor::fromString($html)
    ->autolink() // automatically wrap urls in links
    ->localizeQuotes('en_US') // localize quotes based on locale
    ->processLinks(fn ($el) => $el->setAttribute('data-my-attr', '')) // add various classes to links
    ->beautify() // prevent widows, remove empty paragraphs
    ->encodeEmails(); // encode email addresses (must be called last!)

```

Browse the <a href="./tests">tests folder</a> for usage examples.