<?php
declare(strict_types=1);

namespace Parsemd\Parsemd;

use Parsemd\Parsemd\Lines\Lines;

abstract class AbstractElement implements Element
{
    protected $type;
    protected $reducible  = true;
    protected $inlinable  = true;
    protected $attributes = [];
    protected $Elements   = [];

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
        $this->Elements = [];
    }

    public function setAttribute(string $attribute, $value) : void
    {
        unset($this->attributes[strtolower($attribute)]);

        $this->attributes[strtolower($attribute)] = [
            'value' => $value,
            'name' => $attribute,
        ];
    }

    public function getAttributes() : array
    {
        return array_reduce(
            $this->attributes,
            function ($carry, $attribute)
            {
                $carry[$attribute['name']] = $attribute['value'];

                return $carry;
            },
            []
        ) ?? [];
    }

    public function getAttribute(string $name)
    {
        return $this->attributes[strtolower($name)]['value'] ?? null;
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
