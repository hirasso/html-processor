<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Service\DOM\LinkProcessor;

use Dom\Element;
use Hirasso\HTMLProcessor\Uri\Uri;
use Hirasso\HTMLProcessor\Uri\UriType;

final readonly class Link
{
    public Uri $uri;

    public UriType $type;
    public null|string $extension;

    public function __construct(
        public Element $el
    ) {
        $this->uri = Uri::fromElement($el);
        $this->type = $this->uri->getType();
        $this->extension = $this->uri->getExtension();
    }

    /**
     * Apply classes with a customizable prefix
     */
    public function addClasses(string $prefix = 'link'): self
    {
        $this->el->classList->add("{$prefix}--{$this->type->value}");

        if ($this->uri->pointsToFile()) {
            $this->el->classList->add("{$prefix}--file");
        }

        if ($this->uri->getFragment()) {
            $this->el->classList->add("{$prefix}--anchor");
        }

        return $this;
    }

    /**
     * Open external links in a new tab by adding [target="_blank"]
     * @param bool $safe add [rel="noopener noreferrer"] to links
     */
    public function openExternalInNewTab(bool $safe = true): self
    {
        if ($this->type !== UriType::External) {
            return $this;
        }

        $this->el->setAttribute('target', '_blank');

        if ($safe) {
            $this->el->setAttribute('rel', "noopener noreferrer");
        }

        return $this;
    }
}
