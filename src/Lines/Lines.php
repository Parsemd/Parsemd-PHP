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
class Lines extends LineIterator implements Iterator, Pointer
{
    private $lines;

    protected $pointer;

    public function __construct(?string $lines = null)
    {
        if (isset($lines))
        {
            $lines = str_replace("\0", "\u{fffd}", $lines);
            $lines = str_replace("\r\n", "\n", $lines);
            $lines = str_replace("\r", "\n", $lines);

            $this->lines = explode("\n", $lines);
        }
        else
        {
            $this->lines = [];
        }

        $this->pointer = new LinePointer(count($this->lines));

        $this->transformWhitespace();
    }

    public function current() : string
    {
        return $this->lines[$this->key()];
    }

    public function setCurrent(string $new) : void
    {
        $this->lines[$this->key()] = $new;

        $this->transformWhitespace($this->key());
    }

    public function lookup(int $position) : ?string
    {
        if ($position >= 0 and $position < $this->pointer->count())
        {
            return $this->lines[$position];
        }

        return null;
    }

    public function currentLtrimUpto(int $max) : string
    {
        $white = strspn($this->current(), ' ');
        $trim  = min([$white, $max]);

        return substr($this->current(), $trim);
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
    ) : void
    {
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

        $this->transformWhitespace($this->key());
    }

    public function pop() : Lines
    {
        $instance = clone($this);

        $this->__construct();

        return $instance;
    }

    public function subset(int $start, ?int $end = null) : Lines
    {
        $Lines = new Lines;

        $end = $end ?? $this->count();

        for ($i = $start; $i < $end; $i++)
        {
            $Lines->append($this->lines[$i]);
        }

        $Lines->rewind();

        return $Lines;
    }

    private function transformWhitespace(?int $at = null) : void
    {
        if ( ! isset($at))
        {
            $n = $this->count();

            for ($i = 0; $i < $n; $i++)
            {
                $this->convertTabsAt($i);
            }
        }
        elseif ($at >= 0 and $at < $this->count())
        {
            $this->convertTabsAt($at);
        }
    }

    private function convertTabsAt(int $at) : void
    {
        if ($at < 0 or $at >= $this->count())
        {
            return;
        }

        $line = $this->lines[$at];

        $whitespace = preg_replace('/^(\s*+)[\s\S]*+/', '$1', $line);

        $this->lines[$at] = self::convertTabs($whitespace).ltrim($line);
    }

    public static function convertTabs(string $whitespace) : string
    {
        $n = 0;

        while (($n = strpos($whitespace, "\t")) !== false)
        {
            $whitespace = substr($whitespace, 0, $n)
                        . str_repeat(' ', 4 - ($n % 4))
                        . substr($whitespace, $n + 1);
        }

        return $whitespace;
    }
}
