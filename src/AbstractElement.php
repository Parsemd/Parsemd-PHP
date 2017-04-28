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

    public function dumpElements() : void
    {
        $this->Elements = array();
    }

    public function setAttribute(string $attribute, $value) : void
    {
        $this->attributes[$attribute] = $value;
    }

    public function getAttributes() : array
    {
        return $this->attributes;
    }

    public function setNonReducible(bool $mode = true) : void
    {
        $this->reducible = ( ! $mode);
    }

    public function setNonInlinable(bool $mode = true) : void
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
