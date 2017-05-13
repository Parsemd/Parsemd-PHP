<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Parsemd\Abstractions\Inlines\Emphasis
    as AbstractEmphasis;

class Emphasis extends AbstractEmphasis implements Inline
{
    protected const MARKERS = [
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

    /**
     * Close emph with the run length $length.
     *
     * Ideally we will close the last opened (strong) emph,
     * but if we cannot, find the first available match (going
     * backwards) and discard all opened after it (going
     * forwards).
     *
     * @param int $length
     * @param array $openSequence
     *
     * @param array
     */
    protected static function close(int $length, array $openSeq) : array
    {
        for ($i = count($openSeq) -1; $i >= 0 and $length; $i--)
        {
            if ($openSeq[$i] === 1 or $length % 2 and $openSeq[$i] % 2)
            {
                $length           -= 1;
                $openSeq[$i] -= 1;
            }

            if ($length > 1 and $openSeq[$i] > 1)
            {
                $stLen = $length - ($length % 2);

                $trim = ($stLen > $openSeq[$i] ?
                         $openSeq[$i] - ($openSeq % 2) : $stLen);

                $length           -= $trim;
                $openSeq[$i] -= $trim;
            }
        }

        /**
         * Slice off any now irrelevant openings:
         * We want to include the last $i touched by the loop, so need at least
         * $i + 1, but the loop will also always tick under by 1, so our $i will
         * be 1 less than the last value in the loop. Hence $i + 2.
        */
        $openSeq = array_slice($openSeq, 0, $i + 2);

        # clean up if last item was fully closed
        if (end($openSeq) === 0)
        {
            array_pop($openSeq);
        }

        return $openSeq;
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
