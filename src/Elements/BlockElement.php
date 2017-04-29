<?php

namespace Aidantwoods\Parsemd\Elements;

use Aidantwoods\Parsemd\Element;
use Aidantwoods\Parsemd\AbstractElement;
use Aidantwoods\Parsemd\Lines\Lines;

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
