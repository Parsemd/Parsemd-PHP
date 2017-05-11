<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Lines;

use Iterator;

/**
 * Stores text and is iterable, but defers iteration task
 * to its LinePointer.
 * Implements the extended functionality provided by
 * Pointer, by again deferring tasks to the LinePointer.
 */
class Line extends LineIterator implements Iterator, Pointer
{
    private $text;
    private $cache = [
                'position' => null,
                'text'     => null
            ];

    protected $pointer;

    public function __construct(?string $line = null)
    {
        $this->text = $line ?? '';

        $this->pointer = new LinePointer(strlen($this->text));
    }

    public function current() : string
    {
        if ($this->pointer->current() !== $this->cache['position'])
        {
            $this->cache = [
                'text'     => $this->lookup($this->key()),
                'position' => $this->pointer->key()
            ];
        }

        return $this->cache['text'];
    }

    public function append(string $text) : void
    {
        $this->text .= $text;

        $this->pointer->extendRange(strlen($text));

        $this->cache = [
            'position' => null,
            'text'     => null
        ];
    }

    public function pop() : Line
    {
        $instance = clone($this);

        $this->__construct();

        return $instance;
    }

    public function strcspnJump(string $mask) : void
    {
        $this->next();

        $this->jump($this->key() + strcspn($this->text, $mask, $this->key()));
    }

    public function subset(int $start, ?int $end = null) : Line
    {
        return new Line($this->substr($start, $end));
    }

    public function substr(int $start, ?int $end = null) : string
    {
        $end = $end ?? $this->count();

        return substr($this->text, $start, $end - $start);
    }

    public function lookup(int $position) : ?string
    {
        if ($position === $this->cache['position'])
        {
            return $this->cache['text'];
        }
        elseif ($position >= 0 and $position < $this->pointer->count())
        {
            return substr($this->text, $position);
        }

        return null;
    }

    public function __toString() : string
    {
        return $this->text;
    }
}
