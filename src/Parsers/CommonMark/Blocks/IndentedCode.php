<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Blocks;

use Parsemd\Parsemd\Lines\Lines;
use Parsemd\Parsemd\Elements\BlockElement;

use Parsemd\Parsemd\Parsers\Block;
use Parsemd\Parsemd\Parsers\Core\Blocks\AbstractBlock;

class IndentedCode extends AbstractBlock implements Block
{
    private $Code;
    private $isComplete = false;

    protected const MARKERS = [
        ' '
    ];

    public static function begin(Lines $Lines) : ?Block
    {
        if (
            strspn($Lines->current(), ' ') >= 4
            and trim($Lines->current()) !== ''
        ) {
            return new static(substr($Lines->current(), 4));
        }

        return null;
    }

    public function parse(Lines $Lines) : bool
    {
        if ($this->isContinuable($Lines))
        {
            $this->Code->appendContent($Lines->currentLtrimUpto(4));

            return true;
        }

        return false;
    }

    public function isContinuable(Lines $Lines) : bool
    {
        if (
            strspn($Lines->current(), ' ') < 4
            and trim($Lines->current()) !== ''
        ) {
            $this->isComplete = true;
        }

        return ! $this->isComplete;
    }

    private function __construct(string $text)
    {
        $this->Element = new BlockElement('pre');
        $this->Code    = new BlockElement('code');

        $this->Element->setNonReducible();
        $this->Element->setNonInlinable();

        $this->Code->setNonReducible();
        $this->Code->setNonInlinable();

        $this->Element->appendElement($this->Code);

        $this->Code->appendContent($text);
    }
}
