<?php

namespace Aidantwoods\Phpmd\Blocks;

use Aidantwoods\Phpmd\Block;
use Aidantwoods\Phpmd\Lines\Lines;

abstract class AbstractBlock implements Block
{
    protected $interrupted = false;

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
}
