<?php

namespace Aidantwoods\Phpmd\Blocks;

use Aidantwoods\Phpmd\Block;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\Structure;
use Aidantwoods\Phpmd\Lines\Lines;

class Heading extends AbstractBlock implements Block
{
    private $Element;

    protected static $markers = array(
        '#'
    );

    public static function isPresent(Lines $Lines) : bool
    {
        return preg_match('/^[#]{1,6}[ ]++[^ ]/', $Lines->current());
    }

    public static function begin(Lines $Lines) : ?Block
    {
        if (
            preg_match(
                '/^([#]{1,6})[ ]++([^ ].*)(?:\1\s*)?$/',
                $Lines->current(),
                $matches
            )
        ) {
            return new static(strlen($matches[1]), $matches[2], $Lines);
        }

        return null;
    }

    public function parse(Lines $Lines) : bool
    {
        return false;
    }

    public function isContinuable(Lines $Lines) : bool
    {
        return false;
    }

    public function getElement() : Element
    {
        return $this->Element;
    }

    private function __construct(int $level, string $text, Lines $Lines)
    {
        $Element = new Element("h$level");

        $this->initPointer = $Lines->key();

        $Element->appendContent($text);

        $id = strtolower(str_replace(' ', '', $text));

        $Element->setAttribute('id', $id);

        $Element->setNonReducible();

        $this->Element = $Element;
    }
}
