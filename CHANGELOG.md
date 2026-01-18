# Changelog

## 0.5.2

### Patch Changes

- de4dc40: Fix phpstan errors
- 5b5af67: Use native `htmlentities` with `$double_encode` set to false to prevent double-encoding

## 0.5.1

### Patch Changes

- 2a683fe: don't require illuminate/collections

## 0.5.0

### Minor Changes

- 34cb0a0: Rename the helper function `html()` to `process()`:

  ```php
  use function Hirasso\HTMLProcessor\process;

  echo process($html)
      ->autolinkUrls() // wrap raw url strings in `<a>` tags
      ->removeEmptyElements() // remove empty paragraphs
      ->encodeEmails() // encode emails to confuse spam bots
      ->typography('de_DE') // fix typography based on locale
      ->processLinks(); // add classes based on link type
  ```

### Patch Changes

- a8f6236: Rename enum `LinkType` to `UrlType`

## 0.4.2

### Patch Changes

- 2a4ce16: Fixes links being considered external if they contained a custom port number

## 0.4.1

### Patch Changes

- 29c68b7: Provide a helper function:

  ```php
  use function Hirasso\HTMLProcessor\process;

  echo process($html)->autolinkUrls();
  ```

## 0.4.0

### Minor Changes

- 6564cae: Change the API for `->typography()`:

  ```php
  use Hirasso\HTMLProcessor\HTMLProcessor;

  echo HTMLProcessor::fromString($html)
      ->typography('de_DE', fn ($typo) => $typo
          ->localizeQuotes() // format quotes based on locale
          ->preventWidows() // prevent widows
      );
  ```

## 0.3.0

### Minor Changes

- c594de9: Make the API for processing links more flexible and intuitive:

  ```php
  use Hirasso\HTMLProcessor\HTMLProcessor;

  echo HTMLProcessor::fromString($html)
      ->processLinks(function ($link, $defaultHandler) {
          if ($link->type->value === 'external') {
              $link->el->setAttribute('target', '_blank');
          }
          $defaultHandler(); // run the default handler
      });
  ```

## 0.2.0

### Minor Changes

- bb7db84: Use a fluid API for registering the Typography options

  ```php
  echo HTMLProcessor::fromString($html)
      ->typography(
          Typography::make('de_DE')
              ->localizeQuotes()
              ->preventWidows()
      );
  ```

## 0.1.4

### Patch Changes

- 3d43518: Fix very specific issues with libxml in certain versions

## 0.1.3

### Patch Changes

- a45f63a: Only allow duplicate IDs with a LIBXML_VERSION lower then 21000.

  Otherwise, HTML might not be parsed correctly (observed with
  tags and multiple lines)

## 0.1.2

### Patch Changes

- 513ad82: Fix widow prevention for text containing HTML entities (umlauts). The length check now correctly counts decoded characters instead of entity byte length, preventing false positives that skipped widow prevention on German text.

## 0.1.1

### Patch Changes

- 46d54e6: Only prevent widows in the last text node of each block element

## 0.1.0

### Minor Changes

- 14851a3: Rework the API:

  ```php
  echo HTMLProcessor::fromString($html)
      ->autolinkUrls() // wrap raw url strings in `<a>` tags
      ->autolinkPrefix('@', 'https://your-instance.social/@') // link @profileName to Mastodon
      ->autolinkPrefix('#', 'https://your-instance.social/tags') // link #hashTag to Mastodon
      ->removeEmptyElements('p') // remove empty paragraphs
      ->encodeEmails() // encode emails to confuse spam bots
      ->typography( // optimize typography
          'de_DE', // currently supported: 'en', 'de', 'fr'
          localizeQuotes: true, // format quotes based on locale
          preventWidows: true // prevent widows
      )
      ->processLinks(function ($el, $type) { // process links by callback
              if ($type === UrlType::External) {
                  $el->setAttribute('target', '_blank');
              }
          },
          addClasses: true // automatically add classes by type (mailto:, tel, internal, external, ...)
      );
  ```

## 0.0.4

### Patch Changes

- a3c8c6e: Run assigned tasks lazily and in an order that makes sense
- 1aba0f8: Refactor code

## 0.0.3

### Patch Changes

- bf92a41: Allow controlling what should be beautified:

  ```php
  HirassoHTMLProcessor::fromString($html)
    ->beautify(
      removeEmptyParagraphs: true,
      preventWidows: true
    );
  ```

## 0.0.2

### Patch Changes

- 60faf71: Generalize social linking

## 0.0.1

### Patch Changes

- 33c8d09: Initial Release
