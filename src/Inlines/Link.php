<?php

namespace Aidantwoods\Phpmd\Inlines;

use Aidantwoods\Phpmd\Inline;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\InlineElement;

use Aidantwoods\Phpmd\Lines\Line;

class Link extends AbstractInline implements Inline
{
    private $Element,
            $width,
            $textStart;

    protected static $markers = array(
        '['
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
                '/^\[((?:[\\\]\]|[^]])++)\][(]\s*+((?:[\\\][)]|[^ )])++)(?:\s*+([\'"])((?:[\\\]\3|(?!\3).)++)\3)?\s*+[)]/',
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
        int $width,
        int $textStart,
        string $text,
        string $href,
        ?string $title = null
    ) {
        $this->width     = $width;
        $this->textStart = $textStart;

        $this->Element = new InlineElement('a');

        $this->Element->setNonNestables(['a']);

        $this->Element->appendContent($text);

        $this->Element->setAttribute('href', $href);

        if (isset($title))
        {
            $this->Element->setAttribute('title', $title);
        }
    }
}
