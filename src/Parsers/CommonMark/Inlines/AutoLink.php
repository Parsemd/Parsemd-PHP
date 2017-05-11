<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Core\Inlines\AbstractInline;

class AutoLink extends AbstractInline implements Inline
{
    protected static $markers = [
        'h', '<', 'm', 'i'
    ];

    public static function parse(Line $Line) : ?Inline
    {
        if ($data = self::parseText($Line))
        {
            return new static(
                $data['text'],
                $data['width'],
                $data['textStart']
            );
        }

        return null;
    }

    private static function parseText(Line $Line) : ?array
    {
        if (
            preg_match(
                '/
                ^([<])?
                (
                    (?:https?|mailto|irc)
                    :[\/]{2}
                    [^.\s]++[.][^.\s]
                    (?(1)[^\s>]++|[^\s]++)
                )
                (?<![.])
                (?(1)[>]|)
                /ix',
                $Line->current(),
                $matches
            )
        ) {
            if ( ! isset($matches[1]))
            {
                $before = $Line->lookup($Line->key() -1) ?? ' ';
                $after = $Line->lookup(
                    $Line->key()+ strlen($matches[0])
                ) ?? ' ';

                if ($before !== ' ' or $after !== ' ')
                {
                    return null;
                }
            }

            return [
                'text'      => $matches[2],
                'textStart' => (isset($matches[1]) ? 1 : 0),
                'width'     => strlen($matches[0])
            ];
        }

        return null;
    }

    private function __construct(string $text, int $width, int $textStart)
    {
        $this->width     = $width;
        $this->textStart = $textStart;

        $this->Element = new InlineElement('a');

        $this->Element->setNonNestables(['a']);
        $this->Element->appendContent($text);
        $this->Element->setAttribute('href', $text);
    }
}
