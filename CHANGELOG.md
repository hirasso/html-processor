# Changelog

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
