<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\HTML;

use Asika\Autolink\Autolink;
use Asika\Autolink\AutolinkOptions;
use Hirasso\HTMLProcessor\Service\Contract\HTMLServiceContract;

/**
 * Makes urls clickable
 */
final readonly class Autolinker implements HTMLServiceContract
{
    public function __construct(
        protected ?AutolinkOptions $options = null,
    ) {
    }

    public function getName(): string
    {
        return 'autolink';
    }

    public function shouldDecodeEntities(): bool
    {
        return true;
    }

    public function run(string $html): string
    {
        $autolink = new Autolink($this->options ?? new AutolinkOptions(
            stripScheme: true,
            textLimit: 35,
            autoTitle: false,
            escape: true,
            linkNoScheme: true
        ));

        $html = $autolink->convert($html);
        $html = $autolink->convertEmail($html);

        return $html;
    }
}
