<?php

namespace Aidantwoods\Phpmd\Blocks;

use Aidantwoods\Phpmd\Block;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\Structure;
use Aidantwoods\Phpmd\Lines\Lines;

class Paragraph extends AbstractBlock implements Block
{
    private $Element;

    protected static $markers = array();

    public static function isPresent(Lines $Lines) : bool
    {
        return (trim($Lines->current()) !== '');
    }

    public static function begin(Lines $Lines) : ?Block
    {
        return new static($Lines);
    }

    public function parse(Lines $Lines) : bool
    {
        if (trim($Lines->current()) === '')
        {
            return true;
        }

        $this->uninterrupt();

        $toCurrentLine = (trim($Lines->lookup($Lines->key() -1)) !== '');

        $this->Element->appendContent(
            ltrim($Lines->current()),
            $toCurrentLine
        );

        return true;
    }

    public function isContinuable(Lines $Lines) : bool
    {
        if (trim($Lines->current()) === '')
        {
            $this->interrupt();
        }

        return true;
    }

    public function getElement() : Element
    {
        return $this->Element;
    }

    private function __construct(Lines $Lines)
    {
        $this->Element = new Element('p');

        $this->Element->setNonReducible();

        $this->parse($Lines);
    }
}
