<?php

namespace Aidantwoods\Phpmd\Lines;

use Iterator;

/**
 * Stores text and is iterable, but defers iteration task
 * to its LinePointer.
 * Implements the extended functionality provided by
 * Pointer, by again deferring tasks to the LinePointer.
 */
class Line extends LineIterator implements Iterator, Pointer
{
    private $text,
            $cache = array(
                'position' => null,
                'text'     => null
            );

    protected $pointer;

    public function __construct(?string $line = null)
    {
        $this->text = $line ?? '';

        $this->pointer = new LinePointer(strlen($this->text));
    }

    public function current() : string
    {
        if ($this->pointer->current() === $this->cache['position'])
        {
            return $this->cache['text'];
        }

        $this->cache['text'] = substr($this->text, $this->pointer->current());
        $this->cache['position'] = $this->pointer->current();

        return $this->cache['text'];
    }

    public function append(string $text)
    {
        $this->text .= $text;

        $this->pointer->extendRange(strlen($text));

        $this->cache = array(
            'position' => null,
            'text'     => null
        );
    }

    public function pop() : Line
    {
        $instance = clone($this);

        $this->__construct();

        return $instance;
    }

    public function strcspnJump(string $mask)
    {
        $this->next();

        $this->jump($this->key() + strcspn($this->text, $mask, $this->key()));
    }

    public function subset(int $start, int $end) : Line
    {
        return new Line(substr($this->text, $start, $end - $start));
    }

    public function substr(int $start, int $end) : string
    {
        return substr($this->text, $start, $end - $start);
    }

    public function lookup(int $position) : ?string
    {
        if ($position >= 0 and $position < $this->pointer->count())
        {
            return substr($this->text, $position);
        }

        return null;
    }
}
