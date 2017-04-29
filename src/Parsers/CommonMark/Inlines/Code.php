<?php

namespace Aidantwoods\Parsemd\Parsers\CommonMark\Inlines;

use Aidantwoods\Parsemd\Parsers\Inline;
use Aidantwoods\Parsemd\Parsers\Core\Inlines\AbstractInline;
use Aidantwoods\Parsemd\Element;
use Aidantwoods\Parsemd\Elements\InlineElement;

use Aidantwoods\Parsemd\Lines\Line;

class Code extends AbstractInline implements Inline
{
    protected static $markers = array(
        '`'
    );

    public static function parse(Line $Line) : ?Inline
    {
        if ($data = self::parseText($Line->current()))
        {
            return new static(
                $data['width'],
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
                '/^([`]++)(.*?[^`])\1(?=[^`]|$)/s',
                $text,
                $matches
            )
        ) {
            return array(
                'text'      => $matches[2],
                'textStart' => strlen($matches[1]),
                'width'     => strlen($matches[0])
            );
        }

        return null;
    }

    private function __construct(
        int    $width,
        int    $textStart,
        string $text
    ) {
        $this->width     = $width;
        $this->textStart = $textStart;

        $this->Element = new InlineElement('code');

        $this->Element->setNonInlinable();
        $this->Element->setNotUnescapeContent();
        $this->Element->setNonNestables(['code']);

        $this->Element->appendContent($text);
    }
}
