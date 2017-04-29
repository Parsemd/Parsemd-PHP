<?php

namespace Aidantwoods\Parsemd\Parsers\CommonMark\Blocks;

use Aidantwoods\Parsemd\Parsers\Block;
use Aidantwoods\Parsemd\Parsers\Core\Blocks\AbstractBlock;
use Aidantwoods\Parsemd\Lines\Lines;
use Aidantwoods\Parsemd\Elements\BlockElement;

class Heading extends AbstractBlock implements Block
{
    protected static $markers = array(
        '#'
    );

    public static function isPresent(Lines $Lines) : bool
    {
        return preg_match('/^[ ]{0,3}+[#]{1,6}\s++\S/', $Lines->current());
    }

    public static function begin(Lines $Lines) : Block
    {
        if (
            preg_match(
                '/^\s*+([#]{1,6})\s++(\S.*)(?:\1\s*)?$/',
                $Lines->current(),
                $matches
            )
        ) {
            return new static(strlen($matches[1]), $matches[2], $Lines);
        }
    }

    public function parse(Lines $Lines) : bool
    {
        return false;
    }

    public function isContinuable(Lines $Lines) : bool
    {
        return false;
    }

    private function __construct(int $level, string $text, Lines $Lines)
    {
        $Element = new BlockElement("h$level");

        $this->initPointer = $Lines->key();

        $Element->appendContent(trim($text));

        $id = strtolower(str_replace(' ', '', $text));

        $Element->setAttribute('id', $id);

        $Element->setNonReducible();

        $this->Element = $Element;
    }
}
