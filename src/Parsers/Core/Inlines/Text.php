<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Core\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Core\Inlines\AbstractInline;

class Text extends AbstractInline implements Inline
{
    protected static $markers = [];

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
