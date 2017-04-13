<?php

namespace Aidantwoods\Phpmd\Blocks;

use Aidantwoods\Phpmd\Block;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\Lines\Lines;

abstract class AbstractBlock implements Block
{
    protected $interrupted = false,
              $Element;

    public static function getMarkers() : array
    {
        return static::$markers;
    }

    public function isInterrupted() : bool
    {
        return $this->interrupted;
    }

    public function interrupt()
    {
        $this->interrupted = true;
    }

    public function uninterrupt()
    {
        $this->interrupted = false;
    }

    public function backtrackCount() : int
    {
        return 0;
    }

    public function getElement() : Element
    {
        return $this->Element;
    }

    public function complete()
    {
        return;
    }
}
