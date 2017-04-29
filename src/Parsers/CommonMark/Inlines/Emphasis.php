<?php

namespace Parsemd\Parsemd\Parsers\CommonMark\Inlines;

use Parsemd\Parsemd\{
    Elements\InlineElement,
    Lines\Line
};

use Parsemd\Parsemd\Parsers\{
    Inline,
    Core\Inlines\AbstractInline
};

class Emphasis extends AbstractInline implements Inline
{
    /**
     * Perhaps the only convenience when parsing emphaises to commonmark
     * compliance:
     *  The number of characters present in each type's delimiter run are
     *  distinct powers of two, so this number may this be used
     *  interchangeably with the following constants intended for bitwise
     *  operations.
     */
    const EM = 0b01;
    const ST = 0b10;

    protected static $markers = array(
        '*', '_'
    );

    public static function parse(Line $Line) : ?Inline
    {
        if ($data = static::parseText($Line))
        {
            return new static(
                $data['width'],
                $data['textStart'],
                $data['text']
            );
        }

        return null;
    }

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
        $root   = self::measureDelimiterRun($Line);
        $marker = $Line->current()[0];
        $start  = $Line->key();

        # ensure there is a root delimiter and it is left flanking
        if ( ! $root or ! self::isLeftFlanking($Line, $root))
        {
            return null;
        }

        # we may be able to determine our emphasis type now
        $offset = ($root < 3 ? $root : ($root % 2 ? null : self::ST));

        $Line = clone($Line);

        $openSequence = array();

        for (; $Line->valid(); $Line->strcspnJump($marker))
        {
            if ($length = self::measureDelimiterRun($Line, $marker))
            {
                $isLf = self::isLeftFlanking($Line, $length);
                $isRf = self::isRightFlanking($Line, $length);

                if (self::canOpen($isLf, $isRf, $openSequence))
                {
                    $openSequence = self::open($length, $openSequence);
                }
                elseif (self::canClose($isLf, $isRf, $length, $root))
                {
                    $openSequence = self::close($length, $openSequence);
                }
                elseif (empty($openSequence))
                {
                    return null;
                }

                $Line->jump($Line->key() + $length -1);
            }

            if (empty($openSequence))
            {
                $Line->next();

                break;
            }
        }

        if (empty($openSequence))
        {
            # if we didn't set the emph type earlier, we can now determine our
            # type based on the length of the closing run
            $offset = $offset ?? ($length % 2 ? self::EM : self::ST);

            return array(
                'text'
                    => $Line->substr($start + $offset, $Line->key() - $offset),
                'textStart'
                    => $offset,
                'width'
                    => $Line->key() - $start
            );
        }

        return null;
    }

    /**
     * Measure a delimiter run as defined in
     * http://spec.commonmark.org/0.27/#delimiter-run
     *
     * Return null if the line pointer has not been placed at the beginning of
     * a valid delimiter run
     *
     * @param Line $Line
     * @param string $marker
     *
     * @return ?int
     */
    protected static function measureDelimiterRun(
        Line   $Line,
        string $marker = null
    ) : ?int
    {
        $marker = $marker ?? '*_';

        if (preg_match('/^(['.$marker.'])\1*+/', $Line->current(), $match))
        {
            if ($Line->lookup($Line->key() -1)[0] === $match[1])
            {
                return null;
            }

            $length = strlen($match[0]);

            $before = $Line->lookup($Line->key() -1) ?? '';
            $after  = $Line->lookup($Line->key() + $length) ?? '';

            /**
             * http://spec.commonmark.org/0.27/#emphasis-and-strong-emphasis
             *
             * Many implementations have also restricted intraword emphasis to
             * the * forms, to avoid unwanted emphasis in words containing
             * internal underscores.
             */
            if (
                $match[1] === '_'
                and (
                    preg_match('/^\p{L}/u', $before)
                    or preg_match('/^\p{L}/u', $after)
                )
            ) {
                return null;
            }

            return $length;
        }

        return null;
    }

    /**
     * Given a Line with the pointer at the begining of an already valid
     * delimiter run, determine whether it is left flanking as defined in
     * http://spec.commonmark.org/0.27/#left-flanking-delimiter-run
     *
     * @param Line $Line
     * @param int $length
     *
     * @return bool
     */
    protected static function isLeftFlanking(Line $Line, int $length) : bool
    {
        $before = $Line->lookup($Line->key() -1)[0] ?? ' ';
        $after  = $Line->lookup($Line->key() + $length)[0] ?? ' ';

        return (
            ! ctype_space($after)
            and (
                ! preg_match('/^\p{P}/u', $after)
                or ctype_space($before)
                or preg_match('/^\p{P}/u', $before)
            )
        );
    }

    /**
     * Given a Line with the pointer at the begining of an already valid
     * delimiter run, determine whether it is right flanking as defined in
     * http://spec.commonmark.org/0.27/#right-flanking-delimiter-run
     *
     * @param Line $Line
     * @param int $length
     *
     * @return bool
     */
    protected static function isRightFlanking(Line $Line, int $length) : bool
    {
        $before = $Line->lookup($Line->key() -1)[0] ?? ' ';
        $after  = $Line->lookup($Line->key() + $length)[0] ?? ' ';

        return (
            ! ctype_space($before)
            and (
                ! preg_match('/^\p{P}/u', $before)
                or ctype_space($after)
                or preg_match('/^\p{P}/u', $after)
            )
        );
    }

    /**
     * Determine whether the given sequence may open an emph or strong emph.
     * Are left but not right flanking, or the left flanking on the first run?
     *
     * @param bool $isLf
     * @param bool $isRf
     * @param array $openSequence
     *
     * @return bool
     */
    protected static function canOpen(
        bool  $isLf,
        bool  $isRf,
        array $openSequence
    ) : bool
    {
        return ($isLf and ( ! $isRf or empty($openSequence)));
    }

    /**
     * Determine whether the given sequence may close an emph or strong emph
     * http://spec.commonmark.org/0.27/#can-open-emphasis
     *
     * If one of the delimiters can both open and close (strong)
     * emphasis, then the sum of the lengths of the delimiter runs
     * containing the opening and closing delimiters must not be a
     * multiple of 3.
     *
     * @param bool $isLf
     * @param bool $isRf
     * @param int $length
     * @param int $root
     *
     * @return bool
     */
    protected static function canClose(
        bool $isLf,
        bool $isRf,
        int  $length,
        int  $root
    ) : bool
    {
        return ($isRf and ( ! $isLf or (($length + $root) % 3)));
    }

    /**
     * Open emph with the run length $length.
     *
     * @param int $length
     * @param array $openSequence
     *
     * @return array
     */
    protected static function open(int $length, array $openSequence) : array
    {
        # open an emph, a strong emph, or both
        $openSequence[] = $length;

        return $openSequence;
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
    protected static function close(int $length, array $openSequence) : array
    {
        for ($i = count($openSequence) -1; $i >= 0 and $length; $i--)
        {
            if ($length % 2 and $openSequence[$i] % 2)
            {
                $length           -= self::EM;
                $openSequence[$i] -= self::EM;
            }

            if ($length > 1 and $openSequence[$i] > 1)
            {
                $stLen = $length - ($length % 2);

                $trim = ($stLen > $openSequence[$i] ?
                         $stLen - $openSequence[$i] : $stLen);

                $length           -= $trim;
                $openSequence[$i] -= $trim;
            }
        }

        /**
         * Slice off any now irrelevant openings:
         * We want to include the last $i touched by the loop, so need at least
         * $i + 1, but the loop will also always tick under by 1, so our $i will
         * be 1 less than the last value in the loop. Hence $i + 2.
        */
        $openSequence = array_slice($openSequence, 0, $i + 2);

        # clean up if last item was fully closed
        if (end($openSequence) === 0)
        {
            array_pop($openSequence);
        }

        return $openSequence;
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
