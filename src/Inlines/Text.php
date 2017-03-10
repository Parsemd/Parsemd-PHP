<?php

namespace Aidantwoods\Phpmd\Inlines;

use Aidantwoods\Phpmd\Inline;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\InlineElement;

use Aidantwoods\Phpmd\Lines\Line;

class Text extends AbstractInline implements Inline
{
    private $Element,
            $width,
            $textWidth,
            $textStart;

    protected static $markers = array();

    public function getElement() : Element
    {
        return $this->Element;
    }

    public function getWidth() : int
    {
        return $this->width;
    }

    public function getTextWidth() : int
    {
        return $this->textWidth;
    }

    public function getTextStart() : int
    {
        return $this->textStart;
    }

    // public function append(string $text)
    // {
    //     $this->Element->appendContent($text);

    //     $this->width += strlen($text);
    // }

    public static function parse(Line $Line) : ?Inline
    {
        return new static($Line->current());
    }

    private function __construct(string $text)
    {
        $this->textWidth = $this->width = strlen($text);

        $this->textStart = 0;

        $this->Element = new InlineElement('text');

        $this->Element->setNonInlinable();

        $this->Element->appendContent($text);
    }
}