<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Core\Inlines\AbstractInline;

class Image extends AbstractInline implements Inline
{
    protected static $markers = [
        '!'
    ];

    public static function parse(Line $Line) : ?Inline
    {
        $Line = clone($Line);
        $Line->next();

        if ($Line->valid() and ($Link = Link::parse($Line)))
        {
            return new static($Link);
        }

        return null;
    }

    private function __construct(Link $Link)
    {
        $this->width     = $Link->getWidth()     + 1;
        $this->textStart = $Link->getTextStart() + 1;

        $Element         = $Link->getElement();
        $attributes      = array_change_key_case($Element->getAttributes());

        $this->Element = new InlineElement('img');

        $this->Element->appendContent((string) $Element->getContent());

        $this->Element->setAttribute('src', $attributes['href']);

        if (isset($attributes['title']))
        {
            $this->Element->setAttribute('title', $attributes['title']);
        }
    }
}
