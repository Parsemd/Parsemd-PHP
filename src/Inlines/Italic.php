<?php

namespace Aidantwoods\Phpmd\Inlines;

use Aidantwoods\Phpmd\Inline;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\InlineElement;

use Aidantwoods\Phpmd\Lines\Line;

class Italic extends Bold implements Inline
{
    protected static function parseText(string $text) : ?array
    {
        if (
            preg_match(
                '/^[*]([^\s](?:[*]{2}|.)*?)(?<=[^\s])(?:(?<=[^[:punct:]])[*]|[*](?=[\s]|$|[[:punct:]]))(?![*])/',
                $text,
                $matches
            )
            or
            preg_match(
                '/^[_]([^\s](?:[_]{2}|.)*?)(?<=[^\s])(?:(?<=[^[:punct:]])[_]|[_](?=[\s]|$|[[:punct:]]))(?![\w])(?![_])/',
                $text,
                $matches
            )
        ) {
            return array(
                'text'      => $matches[1],
                'textStart' => 1,
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

        $this->Element = new InlineElement('em');

        $this->Element->appendContent($text);
    }
}
