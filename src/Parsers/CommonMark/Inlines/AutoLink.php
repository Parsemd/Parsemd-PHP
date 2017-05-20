<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Core\Inlines\AbstractInline;

class AutoLink extends AbstractInline implements Inline
{
    protected const MARKERS = [
        '<'
    ];

    protected const ABSOLUTE_URI = '[a-z][a-z0-9+.-]{1,31}+:[^\s[:cntrl:]<>]*+';

    public static function parse(Line $Line) : ?Inline
    {
        if ($data = static::parseText($Line))
        {
            return new static(
                $data['text'],
                $data['width'],
                $data['textStart']
            );
        }

        return null;
    }

    protected static function parseText(Line $Line) : ?array
    {
        if (
            preg_match(
                '/^<('.self::ABSOLUTE_URI.')>/i',
                $Line->current(),
                $matches
            )
        ) {
            return [
                'text'      => $matches[1],
                'textStart' => 1,
                'width'     => strlen($matches[0])
            ];
        }

        return null;
    }

    protected function __construct(string $text, int $width, int $textStart)
    {
        $this->width     = $width;
        $this->textStart = $textStart;

        $this->Element = new InlineElement('a');

        $this->Element->setNonNestables(['a']);
        $this->Element->appendContent($text);
        $this->Element->setNonInlinable();
        $this->Element->setAttribute('href', $text);
    }
}
