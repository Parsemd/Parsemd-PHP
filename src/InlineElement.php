<?php

namespace Aidantwoods\Phpmd;

use Aidantwoods\Phpmd\Lines\Line;

class InlineElement extends Element
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

    public function appendContent(
        string $content,
        bool $toCurrentLine = false,
        bool $withSpace = true
    ) {
        $this->Line->append($content);
    }

    public function getContent()
    {
        return $this->Line;
    }

    public function appendElement(Element $Element)
    {
        $this->Elements[] = $Element;
    }

    public function setNoUnescapeContent(bool $mode = true)
    {
        $this->unescape = ( ! $mode);
    }

    public function isUnescapeContent() : bool
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
}
