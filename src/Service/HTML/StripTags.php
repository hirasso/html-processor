<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\HTML;

use Hirasso\HTMLProcessor\Service\Contract\HTMLServiceContract;
use Hirasso\HTMLProcessor\Service\Trait\HasDefaultPrio;

/**
 * Encodes email addresses found in the HTML to make it a little harder for bots
 */
final readonly class StripTags implements HTMLServiceContract
{
    use HasDefaultPrio;

    /**
     * @param string|list<string>|null $allowed_tags
     */
    public function __construct(
        private string|array|null $allowed_tags
    ) {
    }

    public function run(string $html): string
    {
        return strip_tags($html, $this->allowed_tags);
    }
}
