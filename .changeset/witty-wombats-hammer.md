---
"html-processor": minor
---

Add new typography service `normalizeQuotes`

```php
use function Hirasso\HTMLProcessor\process;
echo process($html)->typography('en', fn ($typo) => $typo->normalizeQuotes());
```