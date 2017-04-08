<?php

namespace Aidantwoods\Phpmd\Inlines;

use Aidantwoods\Phpmd\Block;
use Aidantwoods\Phpmd\Inline;
use Aidantwoods\Phpmd\Element;

abstract class AbstractInline implements Inline
{
    protected $Element,
              $width,
              $textStart;

    public function getElement() : Element
    {
        return $this->Element;
    }

    public function getWidth() : int
    {
        return $this->width;
    }

    public function getTextStart() : int
    {
        return $this->textStart;
    }

    public static function getMarkers() : array
    {
        return static::$markers;
    }

    public function getTextWidth() : int
    {
        return $this->getElement()->getContent()->count();
    }

    public function __clone()
    {
        $this->Element = clone($this->Element);
    }
}
