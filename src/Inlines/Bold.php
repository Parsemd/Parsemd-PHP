<?php

namespace Aidantwoods\Phpmd\Inlines;

use Aidantwoods\Phpmd\Inline;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\InlineElement;

use Aidantwoods\Phpmd\Lines\Line;

class Bold extends AbstractInline implements Inline
{
    protected $Element,
              $width,
              $textStart;

    protected static $markers = array(
        '*', '_'
    );

    public function getElement() : Element
    {
        return $this->Element;
    }

    public function getWidth() : int
    {
        return $this->width;
    }

    public function getTextStart() : int
    {
        return $this->textStart;
    }

    public static function parse(Line $Line) : ?Inline
    {
        $m = $Line->current()[0];

        if (
            (
                $m === '*'
                or (
                    $m === '_'
                    and self::isRegexBeforeMarker(
                        $Line,
                        '/^(?:[^\w]|[_])/',
                        $m
                    )
                )
            )
            and preg_match('/^['.$m.']+(?=[^\s])(?!$)/', $Line->current())
            and (
                preg_match('/^['.$m.']+(?![[:punct:]])/', $Line->current())
                or self::isRegexBeforeMarker($Line, '/^[\s[:punct:]]/', $m)
            )
        ) {
            if ($data = static::parseText($Line->current()))
            {
                return new static(
                    $data['width'],
                    $data['textStart'],
                    $data['text']
                );
            }
        }

        return null;
    }

    protected static function isRegexBeforeMarker(
        Line $Line,
        string $regex,
        string $marker,
        bool $trueIfMarkerAtStart = true
    ) : bool
    {
        $Line = clone($Line);

        for ($Line->before(); $Line->valid(); $Line->before())
        {
            if ($Line->current()[0] === $marker)
            {
                continue;
            }

            return preg_match($regex, $Line->current());
        }

        return $trueIfMarkerAtStart;
    }

    protected static function parseText(string $text) : ?array
    {
        if (
            preg_match(
                '/^[*]{2}([^\s](?:.)*?)(?<=[^\s])(?:(?<=[^[:punct:]])[*]{2}|[*]{2}(?=[\s]|$|[[:punct:]]))(?![*])/',
                $text,
                $matches
            )
            or
            preg_match(
                '/^[_]{2}([^\s](?:.)*?)(?<=[^\s])(?:(?<=[^[:punct:]])[_]{2}|[_]{2}(?=[\s]|$|[[:punct:]]))(?![\w])(?![_])/',
                $text,
                $matches
            )
        ) {
            return array(
                'text'      => $matches[1],
                'textStart' => 2,
                'width'     => strlen($matches[0])
            );
        }

        return null;
    }

    protected function __construct(
        int $width,
        int $textStart,
        string $text
    ) {
        $this->width     = $width;
        $this->textStart = $textStart;

        $this->Element = new InlineElement('strong');

        $this->Element->appendContent($text);
    }
}
