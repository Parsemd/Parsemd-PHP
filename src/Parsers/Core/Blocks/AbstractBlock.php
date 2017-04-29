<?php

namespace Aidantwoods\Parsemd\Parsers\Core\Blocks;

use Aidantwoods\Parsemd\Parsers\Block;
use Aidantwoods\Parsemd\Element;
use Aidantwoods\Parsemd\Lines\Lines;

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

    public function interrupt() : void
    {
        $this->interrupted = true;
    }

    public function uninterrupt() : void
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

    public function complete() : void
    {
        return;
    }
}
