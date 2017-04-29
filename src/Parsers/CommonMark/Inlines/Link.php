<?php

namespace Parsemd\Parsemd\Parsers\CommonMark\Inlines;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Core\Inlines\AbstractInline;
use Parsemd\Parsemd\Element;
use Parsemd\Parsemd\Elements\InlineElement;

use Parsemd\Parsemd\Lines\Line;

class Link extends AbstractInline implements Inline
{
    protected static $markers = array(
        '['
    );

    public static function parse(Line $Line) : ?Inline
    {
        if ($data = self::parseText($Line->current()))
        {
            return new static(
                $data['width'],
                $data['textStart'],
                $data['text'],
                $data['href'],
                $data['title']
            );
        }

        return null;
    }

    private static function parseText(string $text) : ?array
    {
        if (
            preg_match(
                '/
                ^
                \[((?:[\\\]\]|\[(?1)*?\]|[^]])++)\]
                [(]\s*+
                ((?:[\\\][)]|[^ )])++)
                (?:\s*+([\'"])((?:[\\\]\3|(?!\3).)++)\3)?
                \s*+[)]
                /x',
                $text,
                $matches
            )
        ) {
            return array(
                'text'      => $matches[1],
                'textStart' => 1,
                'width'     => strlen($matches[0]),
                'href'      => $matches[2],
                'title'     => $matches[4] ?? null
            );
        }

        return null;
    }

    private function __construct(
        int     $width,
        int     $textStart,
        string  $text,
        string  $href,
        ?string $title = null
    ) {
        $this->width     = $width;
        $this->textStart = $textStart;

        $this->Element = new InlineElement('a');

        $this->Element->appendContent($text);

        $this->Element->setAttribute('href', $href);

        if (isset($title))
        {
            $this->Element->setAttribute('title', $title);
        }
    }
}
