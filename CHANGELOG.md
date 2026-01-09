# Changelog

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
