<?php

namespace Aidantwoods\Parsemd\Parsers\CommonMark\Blocks;

use Aidantwoods\Parsemd\Parsers\Block;
use Aidantwoods\Parsemd\Parsers\Core\Blocks\AbstractBlock;
use Aidantwoods\Parsemd\Lines\Lines;
use Aidantwoods\Parsemd\Elements\BlockElement;

class Quote extends AbstractBlock implements Block
{
    private const SHORT = 0b01,
                  LONG  = 0b10;

    protected $semiInterrupts = array(),
              $marker = self::LONG;

    protected static $markers = array(
        '>'
    );

    public static function isPresent(
        Lines   $Lines,
        ?string &$text   = null,
        ?int    &$marker = null
    ) : bool
    {
        if (preg_match('/^[ ]{0,3}>(\s?+)(.*+)/', $Lines->current(), $matches))
        {
            $text = (empty($matches[1]) ? '' : ' ').$matches[2];

            if ($matches[1] === "\t")
            {
                $text = "   ${text}";
            }

            $marker = (strlen($matches[1]) === 1 ? self::LONG : self::SHORT);

            return true;
        }

        return false;
    }

    public static function begin(Lines $Lines) : Block
    {
        if (self::isPresent($Lines, $text, $marker))
        {
            return new static($text, $marker);
        }
    }

    public function parse(Lines $Lines) : bool
    {
        if (self::isPresent($Lines, $text, $marker))
        {
            $this->semiInterruptIfApplicable($text);
            $this->adjustMarker($marker, $text);

            $this->Element->appendContent($text);
        }
        else
        {
            $this->Element->appendContent($Lines->current());
        }

        return true;
    }

    public function isContinuable(Lines $Lines) : bool
    {
        if (trim($Lines->current()) === '')
        {
            return false;
        }
        elseif ( ! empty($this->semiInterrupts) and ! self::isPresent($Lines))
        {
            return false;
        }

        return true;
    }

    public function complete() : void
    {
        if ($this->marker === self::LONG)
        {
            $Content = $this->Element->getContent()->pop();

            foreach ($Content as $line)
            {
                $line = (strpos($line, ' ') === 0 ? substr($line, 1) : $line);

                $this->Element->appendContent($line);
            }
        }
    }

    private function __construct(string $text, int $marker)
    {
        $this->Element = new BlockElement('blockquote');

        $this->Element->appendContent($text);

        $this->semiInterruptIfApplicable($text);
        $this->adjustMarker($marker, $text);
    }

    private function semiInterruptIfApplicable(string $text)
    {
        $Lines = new Lines($text);

        if (empty($this->semiInterrupts))
        {
            foreach (['PreCode', 'IndentedCode'] as $class)
            {
                $Block = __NAMESPACE__."\\${class}";

                if ($Block::isPresent($Lines))
                {
                    $this->semiInterrupts[$class] = true;

                    return;
                }
            }
        }
        elseif (isset($this->semiInterrupts['PreCode']))
        {
            if (PreCode::isPresent($Lines))
            {
                unset($this->semiInterrupts['PreCode']);
            }
        }
        elseif (isset($this->semiInterrupts['IndentedCode']))
        {
            if ( ! IndentedCode::isPresent($Lines))
            {
                unset($this->semiInterrupts['IndentedCode']);
            }
        }
    }

    private function adjustMarker(int $marker, string $text)
    {
        if (
            $this->marker === self::LONG
            and $marker   === self::SHORT
            and ! empty($text)
        ) {
            $this->marker = self::SHORT;
        }
    }
}
