<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Blocks;

use Parsemd\Parsemd\{
    Lines\Lines,
    Elements\BlockElement
};

use Parsemd\Parsemd\Parsers\{
    Block,
    Core\Blocks\AbstractBlock
};

class PreCode extends AbstractBlock implements Block
{
    private $Code,
            $initialMarker,
            $isComplete = false;

    protected static $markers = array(
        '`', '~'
    );

    public static function begin(Lines $Lines) : ?Block
    {
        if (
            preg_match(
                '/^\s*+([`]{3,}+|[~]{3,}+)([^\s]*+)[ ]*+$/',
                $Lines->current(),
                $matches
            )
        ) {
            return new static($Lines, $matches[1], $matches[2]);
        }

        return null;
    }

    public function parse(Lines $Lines) : bool
    {
        if ($this->isContinuable($Lines))
        {
            if (rtrim($Lines->current()) === $this->initialMarker)
            {
                $this->isComplete = true;
            }
            else
            {
                $this->Code->appendContent($Lines->current());
            }

            return true;
        }

        return false;
    }

    public function isContinuable(Lines $Lines) : bool
    {
        return ! $this->isComplete;
    }

    private function __construct(
        Lines $Lines,
        string $initialMarker,
        ?string $language = null
    ) {
        $this->initialMarker = $initialMarker;

        $this->Element = new BlockElement('pre');
        $this->Code    = new BlockElement('code');

        $this->Element->setNonReducible();
        $this->Element->setNonInlinable();

        $this->Code->setNonReducible();
        $this->Code->setNonInlinable();

        $this->Element->appendElement($this->Code);

        if ( ! empty($language))
        {
            $this->Code->setAttribute('class', "language-$language");
        }
    }
}
