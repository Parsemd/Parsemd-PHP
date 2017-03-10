<?php

namespace Aidantwoods\Phpmd\Inlines;

use Aidantwoods\Phpmd\Inline;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\InlineElement;

use Aidantwoods\Phpmd\Lines\Line;

class Code extends AbstractInline implements Inline
{
    private $Element,
            $width,
            $textWidth,
            $textStart;

    protected static $markers = array(
        '`'
    );

    public function getElement() : Element
    {
        return $this->Element;
    }

    public function getWidth() : int
    {
        return $this->width;
    }

    public function getTextWidth() : int
    {
        return $this->textWidth;
    }

    public function getTextStart() : int
    {
        return $this->textStart;
    }

    public static function parse(Line $Line) : ?Inline
    {
        if ($data = self::parseText($Line->current()))
        {
            return new static(
                $data['width'],
                $data['textWidth'],
                $data['textStart'],
                $data['text']
            );
        }

        return null;
    }

    private static function parseText(string $text) : ?array
    {
        if (
            preg_match(
                '/^([`]++)(.*?[^`\0])\1(?=[^`]|$)/',
                $text,
                $matches
            )
        ) {
            return array(
                'text'      => $matches[2],
                'textWidth' => strlen($matches[2]),
                'textStart' => strlen($matches[1]),
                'width'     => strlen($matches[0])
            );
        }

        return null;
    }

    private function __construct(
        int $width,
        int $textWidth,
        int $textStart,
        string $text
    ) {
        $this->width     = $width;
        $this->textWidth = $textWidth;
        $this->textStart = $textStart;

        $this->Element = new InlineElement('code');

        $this->Element->setNonInlinable();
        $this->Element->setNoUnescapeContent();
        $this->Element->setNonNestables(['code']);

        $this->Element->appendContent($text);
    }
}