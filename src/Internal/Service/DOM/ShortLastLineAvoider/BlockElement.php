<?php

declare(strict_types=1);

namespace Hirasso\HTMLProcessor\Internal\Service\DOM\ShortLastLineAvoider;

/**
 * Block-level HTML elements
 */
enum BlockElement: string
{
    case Body = 'body';
    case Address = 'address';
    case Article = 'article';
    case Aside = 'aside';
    case Blockquote = 'blockquote';
    case Dd = 'dd';
    case Div = 'div';
    case Dl = 'dl';
    case Dt = 'dt';
    case Fieldset = 'fieldset';
    case Figcaption = 'figcaption';
    case Figure = 'figure';
    case Footer = 'footer';
    case Form = 'form';
    case H1 = 'h1';
    case H2 = 'h2';
    case H3 = 'h3';
    case H4 = 'h4';
    case H5 = 'h5';
    case H6 = 'h6';
    case Header = 'header';
    case Hgroup = 'hgroup';
    case Li = 'li';
    case Main = 'main';
    case Nav = 'nav';
    case Ol = 'ol';
    case P = 'p';
    case Section = 'section';
    case Td = 'td';
    case Th = 'th';
    case Ul = 'ul';

    /**
     * Build an XPath query for all block elements
     */
    public static function xPathSelector(string $prefix = '//'): string
    {
        return implode(' | ', array_map(
            fn (self $el) => "{$prefix}{$el->value}",
            self::cases()
        ));
    }
}
