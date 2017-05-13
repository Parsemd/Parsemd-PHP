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
            preg_match('/^[ ]{4}(.*+)$/', $Lines->current(), $matches)
            and trim($matches[1]) !== ''
        ) {
            return new static($matches[1]);
        }

        return null;
    }

    public function parse(Lines $Lines) : bool
    {
        if ($this->isContinuable($Lines))
        {
            $this->Code->appendContent(
                preg_replace(
                    '/^[ ]{4}(.*+)$/',
                    '$1',
                    $Lines->current()
                )
            );

            return true;
        }

        return false;
    }

    public function isContinuable(Lines $Lines) : bool
    {
        if (
            ! preg_match('/^[ ]{4}(.*+)$/', $Lines->current())
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
