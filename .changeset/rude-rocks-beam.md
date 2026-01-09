---
"html-processor": patch
---

Allow controlling what should be beautified:

```php
HirassoHTMLProcessor::fromString($html)
  ->beautify(
    removeEmptyParagraphs: true,
    preventWidows: true
  );
```