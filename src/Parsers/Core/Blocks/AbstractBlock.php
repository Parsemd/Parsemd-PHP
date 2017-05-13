<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Core\Blocks;

use Parsemd\Parsemd\Parsers\Block;
use Parsemd\Parsemd\Element;
use Parsemd\Parsemd\Lines\Lines;

use RuntimeException;

abstract class AbstractBlock implements Block
{
    protected $interrupted = false;
    protected $Element;

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
