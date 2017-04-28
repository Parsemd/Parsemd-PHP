<?php

namespace Aidantwoods\Phpmd\Elements;

use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\AbstractElement;
use Aidantwoods\Phpmd\Lines\Lines;

class BlockElement extends AbstractElement
{
    protected $Lines;

    public function __construct(string $type)
    {
        $this->type = $type;

        $this->Lines = new Lines;
    }

    public function appendContent(
        string $content,
        bool   $toCurrentLine = false,
        bool   $withSpace     = true
    ) : void
    {
        $this->Lines->append($content, $toCurrentLine, $withSpace);
    }

    public function getContent() : Lines
    {
        return $this->Lines;
    }

    public function appendElement(Element $Element)
    {
        $this->Elements[] = $Element;
    }

    public function __clone()
    {
        parent::__clone();

        $this->Lines = clone($this->Lines);
    }
}
