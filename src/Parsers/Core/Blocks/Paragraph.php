<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Core\Blocks;

use Parsemd\Parsemd\Lines\Lines;
use Parsemd\Parsemd\Elements\BlockElement;

use Parsemd\Parsemd\Parsers\Block;
use Parsemd\Parsemd\Parsers\Core\Blocks\AbstractBlock;

class Paragraph extends AbstractBlock implements Block
{
    protected const MARKERS = [];

    public static function begin(Lines $Lines) : ?Block
    {
        if (trim($Lines->current()) !== '')
        {
            return new static($Lines);
        }

        return null;
    }

    public function parse(Lines $Lines) : bool
    {
        if (trim($Lines->current()) === '')
        {
            return true;
        }

        $this->uninterrupt();

        $lastLine = $Lines->lookup($Lines->key() -1) ?? '';

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
