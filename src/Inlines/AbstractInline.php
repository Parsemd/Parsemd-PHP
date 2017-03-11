<?php

namespace Aidantwoods\Phpmd\Inlines;

use Aidantwoods\Phpmd\Block;
use Aidantwoods\Phpmd\Inline;
use Aidantwoods\Phpmd\Element;

abstract class AbstractInline implements Inline
{
    public static function getMarkers() : array
    {
        return static::$markers;
    }

    public function getTextWidth() : int
    {
        return $this->getElement()->getContent()->count();
    }
}
