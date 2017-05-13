<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Blocks;

use Parsemd\Parsemd\Lines\Lines;
use Parsemd\Parsemd\Elements\BlockElement;

use Parsemd\Parsemd\Parsers\Block;
use Parsemd\Parsemd\Parsers\Core\Blocks\AbstractBlock;

class ThematicBreak extends AbstractBlock implements Block
{
    protected const MARKERS = [
        '-', '_', '*'
    ];

    public static function begin(Lines $Lines) : ?Block
    {
        if (
            preg_match(
                '/^[ ]{0,3}+([-_*])(?:\s*+\1){2,}[\s]*+$/',
                $Lines->current()
            )
        ) {
            return new static();
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

    private function __construct()
    {
        $Element = new BlockElement('hr');

        $Element->setNonReducible();

        $this->Element = $Element;
    }
}
