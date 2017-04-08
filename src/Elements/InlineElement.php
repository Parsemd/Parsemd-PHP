<?php

namespace Aidantwoods\Phpmd\Elements;

use Aidantwoods\Phpmd\AbstractElement;
use Aidantwoods\Phpmd\Lines\Line;

class InlineElement extends AbstractElement
{
    protected $Line,
              $nonNestables = null,
              $reducible    = false,
              $unescape     = true;

    public function __construct(string $type)
    {
        $this->type = $type;

        $this->Line = new Line;
    }

    public function appendContent(string $content)
    {
        $this->Line->append($content);
    }

    public function getContent() : Line
    {
        return $this->Line;
    }

    public function appendElement(InlineElement $Element)
    {
        $this->Elements[] = $Element;
    }

    public function setNotUnescapeContent(bool $mode = true)
    {
        $this->unescape = ( ! $mode);
    }

    public function canUnescapeContent() : bool
    {
        return $this->unescape;
    }

    public function setNonNestables(array $nonNestables)
    {
        $this->nonNestables = $nonNestables;
    }

    public function getNonNestables() : ?array
    {
        return $this->nonNestables;
    }

    public function __clone()
    {
        parent::__clone();

        $this->Line = clone($this->Line);
    }
}
