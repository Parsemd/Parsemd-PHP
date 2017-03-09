<?php

namespace Aidantwoods\Phpmd;

use Aidantwoods\Phpmd\Lines\Lines;

class Element
{
    protected $type,
              $Lines,
              $reducible  = true,
              $inlinable  = true,
              $attributes = array(),
              $Elements   = array();

    public function __construct(string $type)
    {
        $this->type = $type;

        $this->Lines = new Lines;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function appendContent(
        string $content,
        bool $toCurrentLine = false,
        bool $withSpace = true
    ) {
        $this->Lines->append($content, $toCurrentLine, $withSpace);
    }

    public function getContent()
    {
        return $this->Lines;
    }

    public function appendElement(Element $Element)
    {
        $this->Elements[] = $Element;
    }

    public function appendElements(array $Elements)
    {
        $this->Elements = array_merge($this->Elements, $Elements);
    }

    public function getElements() : array
    {
        return $this->Elements;
    }

    public function dumpElements()
    {
        $this->Elements = array();
    }

    public function setAttribute(string $attribute, $value)
    {
        $this->attributes[$attribute] = $value;
    }

    public function getAttributes() : array
    {
        return $this->attributes;
    }

    public function setNonReducible(bool $mode = true)
    {
        $this->reducible = ( ! $mode);
    }

    public function setNonInlinable(bool $mode = true)
    {
        $this->inlinable = ( ! $mode);
    }

    public function isReducible() : bool
    {
        return $this->reducible;
    }

    public function isInlinable() : bool
    {
        return $this->inlinable;
    }
}
