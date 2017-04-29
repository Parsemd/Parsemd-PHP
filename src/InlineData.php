<?php

namespace Parsemd\Parsemd;

use Parsemd\Parsemd\Parsers\Inline;

use Parsemd\Parsemd\Lines\Line;

class InlineData
{
    private $Line,
            $Inline;

    public function __construct(Line $Line, Inline $Inline)
    {
        $this->Line   = clone($Line);
        $this->Inline = $Inline;
    }

    public function start() : int
    {
        return $this->Line->key();
    }

    public function end() : int
    {
        return $this->start() + $this->Inline->getWidth();
    }

    public function textStart() : int
    {
        return $this->start() + $this->Inline->getTextStart();
    }

    public function textEnd() : int
    {
        return $this->textStart() + $this->Inline->getTextWidth();
    }

    public function getInline() : Inline
    {
        return $this->Inline;
    }

    public function getLine() : Line
    {
        return clone($this->Line);
    }
}
