---
"html-processor": minor
---

Make the API for processing links more flexible and intuitive:

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
