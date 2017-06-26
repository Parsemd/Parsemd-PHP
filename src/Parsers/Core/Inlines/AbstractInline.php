<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Core\Inlines;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Element;
use Parsemd\Parsemd\InlineData;

use RuntimeException;

abstract class AbstractInline implements Inline
{
    protected $Element;
    protected $width;
    protected $textStart;
    protected $start = 0;

    public function getElement() : Element
    {
        return $this->Element;
    }

    public function getWidth() : int
    {
        return $this->width;
    }

    public function getTextStart() : int
    {
        return $this->textStart;
    }

    public function getStart() : int
    {
        return $this->start;
    }

    public static function getMarkers() : array
    {
        if (defined('static::MARKERS'))
        {
            return static::MARKERS;
        }

        throw new RuntimeException(
            get_called_class().'::MARKERS has not been defined'
        );
    }

    public function getTextWidth() : int
    {
        return $this->getElement()->getContent()->count();
    }

    public function __clone()
    {
        $this->Element = clone($this->Element);
    }

    public function interrupts(InlineData $Current, InlineData $Next) : bool
    {
        return false;
    }

    public function ignores(InlineData $Current, InlineData $Next) : bool
    {
        return false;
    }
}
