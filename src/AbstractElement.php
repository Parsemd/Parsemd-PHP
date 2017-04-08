<?php

namespace Aidantwoods\Phpmd;

use Aidantwoods\Phpmd\Lines\Lines;

abstract class AbstractElement implements Element
{
    protected $type,
              $reducible  = true,
              $inlinable  = true,
              $attributes = array(),
              $Elements   = array();

    public function getType() : string
    {
        return $this->type;
    }

    public function appendElements(array $Elements)
    {
        foreach ($Elements as $Element)
        {
            $this->appendElement($Element);
        }
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

    public function __clone()
    {
        foreach ($this->Elements as &$Element)
        {
            $Element = clone($Element);
        }
    }
}
