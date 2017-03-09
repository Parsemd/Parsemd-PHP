<?php

namespace Aidantwoods\Phpmd\Lines;

use Iterator;

/**
 * Stores text and is iterable, but defers iteration task
 * to its LinePointer.
 * Implements the extended functionality provided by
 * Pointer, by again deferring tasks to the LinePointer.
 */
class Lines extends LineIterator implements Iterator, Pointer
{
    private $lines;

    protected $pointer;

    public function __construct(?string $lines = null)
    {
        if ( ! empty($lines))
        {
            $this->lines = explode("\n", $lines);
        }
        else
        {
            $this->lines = array();
        }

        foreach ($this->lines as $key => $line)
        {
            $this->lengths[$key] = strlen($line);
        }

        $this->pointer = new LinePointer(count($this->lines));
    }

    public function current() : string
    {
        return $this->lines[$this->pointer->current()];
    }

    /**
     * Like {@see current}, but return a reference
     *
     * @return &string
     */
    public function &currentRef() : string
    {
        return $this->lines[$this->pointer->current()];
    }

    public function count() : int
    {
        return $this->pointer->count();
    }

    /**
     * Lookup the value at $position as if it was a pointer key,
     * do NOT move the internal pointer
     *
     * @param int $position
     *
     * @return ?string
     */
    public function lookup(int $position) : ?string
    {
        if ($position >= 0 and $position < $this->pointer->count())
        {
            return $this->lines[$position];
        }

        return null;
    }

    /**
     * Append $text to the content at {@see current} if $toCurrentLine is set
     * to true, otherwise append as a new line after all elements
     *
     * @param string $text
     * @param bool $toCurrentLine
     *
     * @return &string
     */
    public function append(
        string $line,
        bool $toCurrentLine = false,
        bool $withSpace = true
    ) {
        if ($toCurrentLine and $this->count() > 0)
        {
            $this->lines[$this->pointer->current()]
                .= ($withSpace ? ' ' : '') . $line;
        }
        else
        {
            $this->lines[] = $line;

            $this->pointer->extendRange();

            $this->pointer->jump($this->count() -1);
        }
    }

    public function pop() : Lines
    {
        $instance = clone($this);

        $this->__construct();

        return $instance;
    }

    public function subset(int $start, int $end) : Lines
    {
        $Lines = new Lines;

        for ($i = $start; $i < $end; $i++)
        {
            $Lines->append($this->lines[$i]);
        }

        return $Lines;
    }
}
