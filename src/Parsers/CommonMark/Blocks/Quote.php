<?php

namespace Parsemd\Parsemd\Parsers\CommonMark\Blocks;

use Parsemd\Parsemd\{
    Lines\Lines,
    Elements\BlockElement
};

use Parsemd\Parsemd\Parsers\{
    Block,
    Core\Blocks\AbstractBlock
};

class Quote extends AbstractBlock implements Block
{
    private const SHORT = 0b01,
                  LONG  = 0b10;

    protected $semiInterrupts = array(),
              $marker = self::LONG;

    protected static $markers = array(
        '>'
    );

    protected static function isPresent(
        Lines $Lines,
        ?array &$data = null
    ) : bool
    {
        if (preg_match('/^[ ]{0,3}>(\s?+)(.*+)/', $Lines->current(), $matches))
        {
            $text = (empty($matches[1]) ? '' : ' ').$matches[2];

            if ($matches[1] === "\t")
            {
                $text = "   ${text}";
            }

            $data['text'] = $text;

            $data['marker'] = (
                strlen($matches[1]) === 1 ? self::LONG : self::SHORT
            );

            return true;
        }

        return false;
    }

    public static function begin(Lines $Lines) : ?Block
    {
        if (self::isPresent($Lines, $data))
        {
            return new static($data['text'], $data['marker']);
        }

        return null;
    }

    public function parse(Lines $Lines) : bool
    {
        if (self::isPresent($Lines, $data))
        {
            list($text, $marker) = [$data['text'], $data['marker']];

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

                if ($Block::begin($Lines))
                {
                    $this->semiInterrupts[$class] = true;

                    return;
                }
            }
        }
        elseif (isset($this->semiInterrupts['PreCode']))
        {
            if (PreCode::begin($Lines))
            {
                unset($this->semiInterrupts['PreCode']);
            }
        }
        elseif (isset($this->semiInterrupts['IndentedCode']))
        {
            if ( ! IndentedCode::begin($Lines))
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
