<?php

namespace Parsemd\Parsemd\Parsers\Core\Inlines;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Element;
use Parsemd\Parsemd\Elements\InlineElement;

use Parsemd\Parsemd\Lines\Line;

class Text extends AbstractInline implements Inline
{
    protected static $markers = array();

    public static function parse(Line $Line) : ?Inline
    {
        return new static($Line->current());
    }

    private function __construct(string $text)
    {
        $this->textStart = 0;

        $this->Element = new InlineElement('text');

        $this->Element->setNonInlinable();

        $this->Element->appendContent($text);
    }
}
