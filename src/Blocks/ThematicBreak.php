<?php

namespace Aidantwoods\Phpmd\Blocks;

use Aidantwoods\Phpmd\Block;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\Structure;
use Aidantwoods\Phpmd\Lines\Lines;

class ThematicBreak extends AbstractBlock implements Block
{
    private $Element;

    protected static $markers = array(
        '-', '_', '*'
    );

    public static function isPresent(Lines $Lines) : bool
    {
        return preg_match(
            '/^(?:[-]{3,}+|[_]{3,}+|[*]{3,}+)[\s]*+$/',
            $Lines->current()
        );
    }

    public static function begin(Lines $Lines) : ?Block
    {
        return new static();
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

    private function __construct()
    {
        $Element = new Element("hr");

        $Element->setNonReducible();

        $this->Element = $Element;
    }
}
