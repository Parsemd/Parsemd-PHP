<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\GitHubFlavor\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\CommonMark\Inlines\AutoLink as CommonMarkAutoLink;

class AutoLink extends CommonMarkAutoLink implements Inline
{
    # we'll only officially support http(s), mailto, ftp, and www.
    protected const MARKERS = [
        'h', 'm', 'f', 'w'
    ];

    protected const ABSOLUTE_URI = '[a-z][a-z0-9+.-]{1,31}:[^\s[:cntrl:]<>]*';
    protected const VALID_DOMAIN
        = 'www[.][\w-]+[.][\w.-]+(?:[\/][^\s[:cntrl:]<>]*)?';
    protected const NO_TRAILING_PUNCT = '(?<![?!.,:*_~])';

    # this needs some work to follow spec
    protected static function parseText(Line $Line) : ?array
    {
        if ($Line[-1] !== null and strspn($Line[-1], " \t\n\r*_~(") !== 1)
        {
            return null;
        }

        if (
            preg_match(
                '/^'.self::ABSOLUTE_URI.self::NO_TRAILING_PUNCT.'/iu',
                $Line->current(),
                $matches
            )
        ) {
            return [
                'text'      => $matches[0],
                'textStart' => 0,
                'width'     => strlen($matches[0])
            ];
        }
        elseif (
            preg_match(
                '/^'.self::VALID_DOMAIN.self::NO_TRAILING_PUNCT.'/iu',
                $Line->current(),
                $matches
            )
        ) {
            return [
                'text'      => 'http://'.$matches[0],
                'textStart' => 0,
                'width'     => strlen($matches[0])
            ];
        }

        return null;
    }
}
