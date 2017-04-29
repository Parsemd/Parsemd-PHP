<?php

namespace Aidantwoods\Parsemd\Parsers\CommonMark\Inlines;

use Aidantwoods\Parsemd\Parsers\Inline;
use Aidantwoods\Parsemd\Parsers\Core\Inlines\AbstractInline;
use Aidantwoods\Parsemd\Element;
use Aidantwoods\Parsemd\Elements\InlineElement;

use Aidantwoods\Parsemd\Lines\Line;

class Image extends AbstractInline implements Inline
{
    protected static $markers = array(
        '!'
    );

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

        $this->Element->appendContent($Element->getContent());

        $this->Element->setAttribute('src', $attributes['href']);

        if (isset($attributes['title']))
        {
            $this->Element->setAttribute('title', $attributes['title']);
        }
    }
}
