<?php

namespace Aidantwoods\Parsemd\Parsers\CommonMark\Blocks;

use Aidantwoods\Parsemd\Parsers\Block;
use Aidantwoods\Parsemd\Parsers\Core\Blocks\AbstractBlock;
use Aidantwoods\Parsemd\Lines\Lines;
use Aidantwoods\Parsemd\Elements\BlockElement;

class ThematicBreak extends AbstractBlock implements Block
{
    protected static $markers = array(
        '-', '_', '*'
    );

    public static function isPresent(Lines $Lines) : bool
    {
        return preg_match(
            '/^[ ]{0,3}+([-_*])(?:\s*+\1){2,}[\s]*+$/',
            $Lines->current()
        );
    }

    public static function begin(Lines $Lines) : Block
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

    private function __construct()
    {
        $Element = new BlockElement('hr');

        $Element->setNonReducible();

        $this->Element = $Element;
    }
}
