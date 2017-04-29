<?php

namespace Aidantwoods\Parsemd\Lines;

use Iterator;
use Countable;

/**
 * Stores text and is iterable, but defers iteration task
 * to its LinePointer.
 * Implements the extended functionality provided by
 * Pointer, by again deferring tasks to the LinePointer.
 */
abstract class LineIterator implements Iterator, Pointer, Countable
{
    protected $pointer;

    public function key() : int
    {
        return $this->pointer->key();
    }

    public function next() : void
    {
        $this->pointer->next();
    }

    public function before() : void
    {
        $this->pointer->before();
    }

    public function jump(int $position) : void
    {
        $this->pointer->jump($position);
    }

    public function rewind() : void
    {
        $this->pointer->rewind();
    }

    public function valid() : bool
    {
        return $this->pointer->valid();
    }

    public function count() : int
    {
        return $this->pointer->count();
    }

    /**
     * Return a reference to internal LinePointer so that the pointer can be
     * moved independently
     *
     * @return LinePointer
     */
    public function &getPointer() : LinePointer
    {
        return $this->pointer;
    }

    public function __clone()
    {
        $this->pointer = clone($this->pointer);
    }

    /**
     * Append $text
     *
     * @param string $text
     */
    abstract public function append(string $text) : void;

    /**
     * Lookup the value at $position as if it was a pointer key,
     * do NOT move the internal pointer
     *
     * @param int $position
     *
     * @return ?string
     */
    abstract public function lookup(int $position) : ?string;
}
