<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Parsemd\Abstractions\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\InlineData;
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

    public function interrupts(InlineData $Current, InlineData $Next) : bool
    {
        if ($Current->getInline() instanceof Emphasis)
        {
            /**
             * When there are two potential emphasis or strong emphasis spans
             * with the same closing delimiter, the shorter one (the one that
             * opens later) takes precedence.
             *
             * http://spec.commonmark.org/0.27/#emphasis-and-strong-emphasis
             */
            if (
                $Next->end() === $Current->end()
                and $Next->textEnd() === $Current->textEnd()
                and $Next->start() > $Current->textStart()
            ) {
                return true;
            }

            if (
                $Next->end() >= $Current->textEnd()
                and $Next->textEnd() < $Current->textEnd()
                and $Next->start() > $Current->start()
                and $Next->end() - $Next->textEnd() > $Current->end() - $Current->textEnd()
            ) {
                return true;
            }
        }

        return parent::ignores($Current, $Next);
    }

    public function ignores(InlineData $Current, InlineData $Next) : bool
    {
        if ($Next->getInline() instanceof Emphasis)
        {
            /**
             * When two potential emphasis or strong emphasis spans overlap, so
             * that the second begins before the first ends and ends after the
             * first ends, the first takes precedence.
             *
             * http://spec.commonmark.org/0.27/#emphasis-and-strong-emphasis
             */
            if (
                $Next->start() < $Current->end()
                and $Next->end() > $Current->end()
            ) {
                return true;
            }
        }

        return parent::ignores($Current, $Next);
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
                $sft  = $realRoot - static::shortenToValidModulo($realRoot);

                $lsft += $sft;
                $rsft += $sft;
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

    protected static function shortenToValidModulo(int $length) : int
    {
        $mod = (defined('static::MAX_RUN') ? static::MAX_RUN : $length);

        if (static::STRICT_FAIL)
        {
            return $length;
        }

        while (0 !== ($length % $mod) and static::canGetNearestValid($length))
        {
            $length--;
        }

        return $length;
    }

    protected static function canGetNearestValid(int $length) : bool
    {
        return static::getNearestValid($length) !== null;
    }

    protected static function getNearestValid(int $length) : ?int
    {
        $hasMin = defined('static::MIN_RUN');
        $hasMax = defined('static::MAX_RUN');

        if (
            static::STRICT_FAIL and $hasMax and $length > static::MAX_RUN
            or $length <= 0
        ) {
            return null;
        }

        if ( ! $hasMax and ! $hasMin)
        {
            return $length;
        }

        if (
            $hasMax and static::MAX_RUN <= 0
            or $hasMin and static::MIN_RUN <= 0
            or $hasMin and $hasMax and static::MIN_RUN > static::MAX_RUN
        ) {
            throw new RuntimeException(
                'Bad MAX_RUN/MIN_RUN defined.'
            );
        }

        # if it's too big (or just big enough), we can shrink it to the MAX_RUN
        # (or leave it as MAX_RUN)
        if ($hasMax and $length >= static::MAX_RUN)
        {
            return static::MAX_RUN;
        }
        # if it's too small, we cannot expand it
        elseif ($hasMin and $length < static::MIN_RUN)
        {
            return null;
        }
        # if it's not too small, and not too big, we can leave it as is
        else
        {
            return $length;
        }
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
            (
                ! $isLf
                or $length <= $trail and abs($trail - $length) !== 1
            )
            and ( ! static::STRICT_FAIL or $root === $length)
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
