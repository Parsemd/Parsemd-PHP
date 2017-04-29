<?php

namespace Aidantwoods\Parsemd\Inlines;

use Aidantwoods\Parsemd\Block;
use Aidantwoods\Parsemd\Inline;
use Aidantwoods\Parsemd\Element;

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
