---
"html-processor": minor
---

Use a fluid API for registering the Typography options

```php
echo HTMLProcessor::fromString($html)
    ->typography(
        Typography::make('de_DE')
            ->localizeQuotes()
            ->preventWidows()
    );
```
