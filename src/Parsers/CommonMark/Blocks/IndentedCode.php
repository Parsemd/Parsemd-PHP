<?php

namespace Aidantwoods\Parsemd\Parsers\CommonMark\Blocks;

use Aidantwoods\Parsemd\Parsers\Block;
use Aidantwoods\Parsemd\Parsers\Core\Blocks\AbstractBlock;
use Aidantwoods\Parsemd\Lines\Lines;
use Aidantwoods\Parsemd\Elements\BlockElement;

class IndentedCode extends AbstractBlock implements Block
{
    private $Code,
            $isComplete = false;

    protected static $markers = array(
        ' '
    );

    public static function isPresent(Lines $Lines) : bool
    {
        return preg_match(
            '/^[ ]{4,}+[^ ]/',
            $Lines->current()
        );
    }

    public static function begin(Lines $Lines) : Block
    {
        if (preg_match('/^[ ]{4}(.*+)$/', $Lines->current(), $matches))
        {
            return new static($matches[1]);
        }
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
