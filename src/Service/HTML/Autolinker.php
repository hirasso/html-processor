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
        public AutolinkOptions $options,
    ) {
    }

    /** autolink has to happen before everything else */
    public function prio(): int
    {
        return -10;
    }

    public function run(string $html): string
    {
        $autolink = new Autolink($this->options);

        $html = $autolink->convert($html);
        $html = $autolink->convertEmail($html);

        return $html;
    }
}
