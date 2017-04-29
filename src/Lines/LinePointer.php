<?php

namespace Parsemd\Parsemd\Lines;

use Iterator;
use Countable;

/**
 * Implements the Iterator class, and adds before and jump methods for
 * arbitrary positioning
 */
class LinePointer implements Iterator, Pointer, Countable
{
    private $pointer,
            $length;

    public function __construct(int $length)
    {
        $this->pointer = 0;

        $this->length = $length;
    }

    public function current() : int
    {
        return $this->pointer;
    }

    public function key() : int
    {
        return $this->current();
    }

    public function next() : void
    {
        $this->pointer++;
    }

    public function before() : void
    {
        $this->pointer--;
    }

    public function jump(int $position) : void
    {
        $this->pointer = $position;
    }

    public function rewind() : void
    {
        $this->pointer = 0;
    }

    public function valid() : bool
    {
        return ($this->pointer >= 0 and $this->pointer < $this->length);
    }

    public function count() : int
    {
        return $this->length;
    }

    /**
     * Extend the pointer range by $n
     *
     * @param int $n
     */
    public function extendRange(int $n = 1) : void
    {
        $this->length += $n;
    }
}
