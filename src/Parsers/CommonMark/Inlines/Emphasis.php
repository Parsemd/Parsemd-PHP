<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;

class Emphasis extends AbstractEmphasis implements Inline
{
    protected static $markers = [
        '*', '_'
    ];

    /**
     * The idea here is to parse the outer inline (sub-structures will be
     * parsed recursively).
     *
     * Unfortunately (for performance) the only way to parse two types of
     * emphasis that utilise idential marking characters, that may also be
     * arbitrarily nested, or may just be "literal" is to be aware of
     * substructures as we are parsing the outer one, so that we know the
     * correct place to end.
     *
     * @param Line $Line
     *
     * @return ?array
     */
    protected static function parseText(Line $Line) : ?array
    {
        if ($data = parent::parseText($Line))
        {
            $offset = ($data['textStart'] % 2 ? 1 : 2);
            $start  = $Line->key();
            $end    = $Line->key() + $data['width'];

            return [
                'text'
                    => $Line->substr($start + $offset, $end - $offset),
                'textStart'
                    => $offset,
                'width'
                    => $data['width']
            ];
        }

        return null;
    }

    protected function __construct(
        int    $width,
        int    $textStart,
        string $text
    ) {
        $this->width     = $width;
        $this->textStart = $textStart;

        $this->Element = new InlineElement(($textStart % 2 ? 'em' : 'strong'));

        $this->Element->appendContent($text);
    }
}
