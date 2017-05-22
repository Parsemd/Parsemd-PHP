<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Blocks;

use Parsemd\Parsemd\Lines\Lines;
use Parsemd\Parsemd\Elements\BlockElement;

use Parsemd\Parsemd\Parsers\Block;
use Parsemd\Parsemd\Parsers\Core\Blocks\AbstractBlock;

class PreCode extends AbstractBlock implements Block
{
    private $Code;
    private $initialMarker;
    private $isComplete = false;

    protected const MARKERS = [
        '`', '~'
    ];

    public static function begin(Lines $Lines) : ?Block
    {
        if (
            preg_match(
                '/^\s*+([`]{3,}+|[~]{3,}+)([^\s]*+)[ ]*+$/',
                $Lines->current(),
                $matches
            )
        ) {
            if (strpos($matches[2], $matches[1][0]) !== false)
            {
                return null;
            }

            return new static($Lines, $matches[1], $matches[2]);
        }

        return null;
    }

    public function parse(Lines $Lines) : bool
    {
        if ($this->isContinuable($Lines))
        {
            if (rtrim($Lines->currentLtrimUpto(3)) === $this->initialMarker)
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
