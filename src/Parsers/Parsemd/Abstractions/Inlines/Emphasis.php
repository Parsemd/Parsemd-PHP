<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Parsemd\Abstractions\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Core\Inlines\AbstractInline;

use RuntimeException;

abstract class Emphasis extends AbstractInline implements Inline
{
    protected const STRICT_FAIL = true;

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
        $marker = $Line[0];
        $root   = static::measureDelimiterRun($Line, $marker);
        $start  = $Line->key();

        # ensure there is a root delimiter and it is left flanking
        if (
            ! $root or ! static::isLeftFlanking($Line, $root)
            or (
                ! static::isRunLengthValid($root)
                and ! static::getNearestValid($root)
            )
        ) {
            return null;
        }

        $Line = clone($Line);

        $openSequence = [];

        for (; $Line->valid(); $Line->strcspnJump($marker))
        {
            if ($Line->isEscaped())
            {
                continue;
            }

            if ($length = static::measureDelimiterRun($Line, $marker))
            {
                if ( ! static::isRunLengthValid($length))
                {
                    if ( ! ($length = static::getNearestValid($length)))
                    {
                        continue;
                    }
                }

                $isLf = static::isLeftFlanking($Line, $length);
                $isRf = static::isRightFlanking($Line, $length);

                if (static::canOpen($isLf, $isRf, $openSequence))
                {
                    $openSequence = static::open($length, $openSequence);
                }
                elseif (static::canClose($isLf, $isRf, $length, $root))
                {
                    $openSequence = static::close($length, $openSequence);
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

        if (empty($openSequence) and isset($length))
        {
            $offset = ($root > $length ? $length : $root);

            return [
                'text'
                    => $Line->substr($start + $offset, $Line->key() - $offset),
                'textStart'
                    => $offset,
                'width'
                    => $Line->key() - $start
            ];
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
        string $marker
    ) : ?int
    {
        if ($length = strspn($Line->current(), $marker))
        {
            if ($Line[-1] === $marker and ! $Line->isEscapedAt($Line->key() -1))
            {
                return null;
            }

            $before = $Line[-1] ?? '';
            $after  = $Line[$length] ?? '';

            /**
             * http://spec.commonmark.org/0.27/#emphasis-and-strong-emphasis
             *
             * Many implementations have also restricted intraword emphasis to
             * the * forms, to avoid unwanted emphasis in words containing
             * internal underscores.
             */
            if (
                $marker === '_'
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

    protected static function isRunLengthValid(int $length) : bool
    {
        if (defined('static::MAX_RUN') and $length > static::MAX_RUN)
        {
            return false;
        }
        elseif (defined('static::MIN_RUN') and $length < static::MIN_RUN)
        {
            return false;
        }

        return true;
    }

    protected static function getNearestValid(int $length) : ?int
    {
        if (static::STRICT_FAIL)
        {
            return null;
        }

        if (defined('static::MAX_RUN') and $length > static::MAX_RUN)
        {
            if (static::MAX_RUN > 0)
            {
                return static::MAX_RUN;
            }
        }
        elseif (defined('static::MIN_RUN') and $length < static::MIN_RUN)
        {
            return null;
        }

        throw new RuntimeException(
            'Bad MAX_RUN defined, or length already valid.'
        );
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
        $before = $Line[-1] ?? ' ';
        $after  = $Line[$length] ?? ' ';

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
        $before = $Line[-1] ?? ' ';
        $after  = $Line[$length] ?? ' ';

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
        return $isRf;
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
     * @param int $length
     * @param array $openSequence
     *
     * @param array
     */
    protected static function close(int $length, array $openSequence) : array
    {
        for ($i = count($openSequence) -1; $i >= 0 and $length; $i--)
        {
            if ($length > $openSequence[$i])
            {
                $length -= $openSequence[$i];
                $openSequence[$i] = 0;
            }
            else
            {
                $openSequence[$i] -= $length;
                $length = 0;
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

        $this->Element = new InlineElement(static::TAG);

        $this->Element->appendContent($text);
    }
}
