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
            $lines = str_replace("\0", "\u{fffd}", $lines);
            $lines = str_replace("\r\n", "\n", $lines);
            $lines = str_replace("\r", "\n", $lines);

            $this->lines = explode("\n", $lines);
        }
        else
        {
            $this->lines = array();
        }

        $this->pointer = new LinePointer(count($this->lines));
    }

    public function current() : string
    {
        return $this->lines[$this->key()];
    }

    public function setCurrent(string $new)
    {
        $this->lines[$this->key()] = $new;
    }

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
        bool   $toCurrentLine = false,
        bool   $withSpace     = true
    ) {
        if ($toCurrentLine and $this->count() > 0)
        {
            $this->lines[$this->key()]
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
