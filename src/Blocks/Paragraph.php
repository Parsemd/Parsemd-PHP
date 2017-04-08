<?php

namespace Aidantwoods\Phpmd\Blocks;

use Aidantwoods\Phpmd\Block;
use Aidantwoods\Phpmd\Structure;
use Aidantwoods\Phpmd\Lines\Lines;
use Aidantwoods\Phpmd\Elements\BlockElement;

class Paragraph extends AbstractBlock implements Block
{
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

        $lastLine = $Lines->lookup($Lines->key() -1);

        # append to the current line or a new one?

        $toCurrentLine = (trim($lastLine) !== '');
        $toCurrentLine = ! ( ! $toCurrentLine ?:substr($lastLine, -2) === '  ');

        $this->Element->appendContent(
            trim($Lines->current()),
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

    private function __construct(Lines $Lines)
    {
        $this->Element = new BlockElement('p');

        $this->Element->setNonReducible();

        $this->parse($Lines);
    }
}
