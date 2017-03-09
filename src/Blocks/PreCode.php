<?php

namespace Aidantwoods\Phpmd\Blocks;

use Aidantwoods\Phpmd\Block;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\Structure;
use Aidantwoods\Phpmd\Lines\Lines;

class PreCode extends AbstractBlock implements Block
{
    private $Element,
            $Code,
            $initialMarker,
            $isComplete = false;

    protected static $markers = array(
        '`', '~'
    );

    public static function isPresent(Lines $Lines) : bool
    {
        return preg_match(
            '/^(?:[`]{3,}+|[~]{3,}+)[^\s]*+[ ]*+$/',
            $Lines->current()
        );
    }

    public static function begin(Lines $Lines) : ?Block
    {
        if (
            preg_match(
                '/^('.implode('{3,}|', self::$markers).'{3,})([^\s]*+)[ ]*+$/',
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

    public function getElement() : Element
    {
        return $this->Element;
    }

    private function __construct(
        Lines $Lines,
        string $initialMarker,
        ?string $language = null
    ) {
        $this->initialMarker = $initialMarker;

        $this->Element = new Element('pre');

        $this->Code = new Element('code');

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
