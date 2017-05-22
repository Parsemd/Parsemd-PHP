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
    protected const STRICT_FAIL = false;
    protected const INTRAWORD_MARKER_BLACKLIST = [];

    public static function parse(Line $Line) : ?Inline
    {
        if ($data = static::parseText($Line))
        {
            return new static(
                $data['width'],
                $data['textStart'],
                $data['start'],
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
                and ! (static::canGetNearestValid($root))
            )
        ) {
            return null;
        }

        $Line = clone($Line);

        $trail = 0;

        for (; $Line->valid(); $Line->strcspnJump($marker))
        {
            if ($Line->isEscaped())
            {
                continue;
            }

            if ($length = static::measureDelimiterRun($Line, $marker))
            {
                $isLf = static::isLeftFlanking($Line, $length);
                $isRf = static::isRightFlanking($Line, $length);

                if (static::canOpen($isLf, $isRf, $length, $trail))
                {
                    $trail = static::open($length, $trail);
                }
                elseif (static::canClose($isLf, $isRf, $length, $trail, $root))
                {
                    $close = [
                        'key'    => $Line->key(),
                        'length' => $length,
                        'trail'  => $trail,
                    ];

                    $trail = static::close($length, $trail);
                }
                elseif ($trail === 0)
                {
                    return null;
                }

                $Line->jump($Line->key() + $length -1);
            }

            if ($trail === 0)
            {
                $Line->next();

                break;
            }
        }

        if (isset($close))
        {
            $lsft = 0;
            $rsft = 0;

            if ( ! static::isRunLengthValid($close['length']))
            {
                if ( ! (static::canGetNearestValid($close['length'])))
                {
                    return null;
                }
            }

            $realRoot = $root;

            if ( ! static::isRunLengthValid($root))
            {
                $root = static::getNearestValid($root);
            }

            $len = min([$root, $close['length']]);

            # some cases when we do not close perfectly:

            # when the root run is too long
            if ($close['length'] < $realRoot and $trail)
            {
                $lsft = $realRoot - $close['length'];
            }
            # when the close run is too long
            if ($close['trail'] < $close['length'])
            {
                $sft = $close['length'] - $close['trail'];

                if (static::canGetNearestValid($close['length'] - $sft))
                {
                    $rsft += $sft;
                }
            }
            # when we can tighten both ends
            if (
                $trail === 0 and $realRoot > $root
                and $close['length'] > $root
            ) {
                $sft  = $realRoot - $root;

                if ( ! static::canGetNearestValid($sft))
                {
                    $lsft += $sft;
                    $rsft += $sft;
                }
            }

            $start += $lsft;
            $end = $Line->key() - $rsft;

            return [
                'text'
                    => $Line->substr($start + $len, $end - $len),
                'textStart'
                    => $len,
                'start'
                    => $lsft,
                'width'
                    => $end - $start
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
                in_array($marker, static::INTRAWORD_MARKER_BLACKLIST, true)
                and (
                    preg_match('/^[[:alnum:]]{2}/u', $before.$after)
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

    protected static function canGetNearestValid(int $length) : bool
    {
        if (static::STRICT_FAIL)
        {
            return false;
        }

        if ( ! defined('static::MAX_RUN') and ! defined('static::MIN_RUN'))
        {
            return true;
        }

        if (defined('static::MAX_RUN') and $length >= static::MAX_RUN)
        {
            if (static::MAX_RUN > 0)
            {
                return true;
            }
        }
        elseif (defined('static::MIN_RUN'))
        {
            if ($length < static::MIN_RUN)
            {
                return false;
            }
            elseif (defined('static::MAX_RUN') and $length < static::MAX_RUN)
            {
                return true;
            }
        }
        elseif (defined('static::MAX_RUN') and $length < static::MAX_RUN)
        {
            return true;
        }


        throw new RuntimeException(
            'Bad MAX_RUN defined, or length already valid.'
        );
    }

    protected static function getNearestValid(int $length) : ?int
    {
        if (static::STRICT_FAIL)
        {
            return null;
        }

        if ( ! defined('static::MAX_RUN') and ! defined('static::MIN_RUN'))
        {
            return $length;
        }

        if (defined('static::MAX_RUN') and $length >= static::MAX_RUN)
        {
            if (static::MAX_RUN > 0)
            {
                return static::MAX_RUN;
            }
        }
        elseif (defined('static::MIN_RUN'))
        {
            if ($length < static::MIN_RUN)
            {
                return false;
            }
            elseif (defined('static::MAX_RUN') and $length < static::MAX_RUN)
            {
                return $length;
            }
        }
        elseif (defined('static::MAX_RUN') and $length < static::MAX_RUN)
        {
            return $length;
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
                ! preg_match('/^\p{P}/', $after)
                or ctype_space($before)
                or preg_match('/^\p{P}/', $before)
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
                ! preg_match('/^\p{P}/', $before)
                or ctype_space($after)
                or preg_match('/^\p{P}/', $after)
            )
        );
    }

    /**
     * Determine whether the given sequence may open
     *
     * @param bool $isLf
     * @param bool $isRf
     * @param int  $length
     * @param int  $trail
     *
     * @return bool
     */
    protected static function canOpen(
        bool $isLf,
        bool $isRf,
        int  $length,
        int  $trail
    ) : bool
    {
        return ($isLf and ( ! $isRf or $length > $trail));
    }

    /**
     * Determine whether the given sequence may close
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
        int  $trail,
        int  $root
    ) : bool
    {
        return $isRf and (
             ! $isLf or $length <= $trail
             and abs($trail - $length) !== 1
        );
    }

    /**
     * Open emph with the run length $length.
     *
     * @param int $length
     * @param int $trial
     *
     * @return int
     */
    protected static function open(int $length, int $trail) : int
    {
        return $trail + $length;
    }

    /**
     * Close emph with the run length $length.
     *
     * @param int $length
     * @param int $trail
     *
     * @param int
     */
    protected static function close(int $length, int $trail) : int
    {
        $trail -= $length;

        if ($trail < 0)
        {
            $trail = 0;
        }

        return $trail;
    }

    protected function __construct(
        int    $width,
        int    $textStart,
        int    $start,
        string $text
    ) {
        $this->start     = $start;
        $this->width     = $width;
        $this->textStart = $textStart;

        $this->Element = new InlineElement(static::TAG);

        $this->Element->appendContent($text);
    }
}
