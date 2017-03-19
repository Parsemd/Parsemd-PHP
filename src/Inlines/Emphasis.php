<?php

namespace Aidantwoods\Phpmd\Inlines;

use Aidantwoods\Phpmd\Inline;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\InlineElement;

use Aidantwoods\Phpmd\Lines\Line;

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

    protected $Element,
              $width,
              $textStart;

    protected static $markers = array(
        '*', '_'
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

                list($isEmph, $isStrong) = self::isEmphOrStrong($length);

                if (self::canOpen($isLf, $isRf, $openSequence))
                {
                    self::open($isEmph, $isStrong, $openSequence);
                }
                elseif (self::canClose($isLf, $isRf, $length, $root))
                {
                    self::close($isEmph, $isStrong, $openSequence);
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

    protected static function measureDelimiterRun(
        Line $Line,
        string $marker = null
    ) : ?int
    {
        $marker = $marker ?? '*_';

        /**
         * http://spec.commonmark.org/0.27/#delimiter-run
         *
         * A delimiter run is either a sequence of one or more ('*', '_')
         * characters that is not preceded or followed by a ('*', '_')
         * character respectively.
         */
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
                    preg_match('/^\w/', $before)
                    or preg_match('/^\w/', $after)
                )
            ) {
                return null;
            }

            return $length;
        }

        return null;
    }

    /**
     * http://spec.commonmark.org/0.27/#left-flanking-delimiter-run
     */
    protected static function isLeftFlanking(Line $Line, int $length) : bool
    {
        $before = $Line->lookup($Line->key() -1)[0] ?? ' ';
        $after  = $Line->lookup($Line->key() + $length)[0] ?? ' ';

        return (
            $after !== ' '
            and (
                ! preg_match('/^[[:punct:]]$/', $after)
                or $before === ' '
                or preg_match('/^[[:punct:]]$/', $before)
            )
        );
    }

    /**
     * http://spec.commonmark.org/0.27/#right-flanking-delimiter-run
     */
    protected static function isRightFlanking(Line $Line, int $length) : bool
    {
        $before = $Line->lookup($Line->key() -1)[0] ?? ' ';
        $after  = $Line->lookup($Line->key() + $length)[0] ?? ' ';

        return (
            $before !== ' '
            and (
                ! preg_match('/^[[:punct:]]$/', $before)
                or $after === ' '
                or preg_match('/^[[:punct:]]$/', $after)
            )
        );
    }

    protected static function isEmphOrStrong(int $length) : array
    {
        # can we end/begin and emph or strong emph?
        $isEmph = (bool) ($length % 2);

        $isStrong = ( ! $isEmph ?: $length > 1);

        return array($isEmph, $isStrong);
    }

    /**
     * Are left but not right flanking, or the left flanking on the first run?
     */
    protected static function canOpen(
        bool $isLf,
        bool $isRf,
        array $openSequence
    ) : bool
    {
        return ($isLf and ( ! $isRf or empty($openSequence)));
    }

    /**
     * http://spec.commonmark.org/0.27/#can-open-emphasis
     *
     * If one of the delimiters can both open and close (strong)
     * emphasis, then the sum of the lengths of the delimiter runs
     * containing the opening and closing delimiters must not be a
     * multiple of 3.
     */
    protected static function canClose(
        bool $isLf,
        bool $isRf,
        int $length,
        int $root
    ) : bool
    {
        return ($isRf and ( ! $isLf or (($length + $root) % 3)));
    }

    protected static function open(
        bool $isEmph,
        bool $isStrong,
        array &$openSequence
    ) {
        # open an emph, a strong emph, or both
        $openSequence[] = ($isEmph ? self::EM : 0) | ($isStrong ? self::ST : 0);
    }

    /**
     * Ideally we will close the last opened (strong) emph,
     * but if we cannot, find the first available match (going
     * backwards) and discard all opened after it (going
     * forwards)
     */
    protected static function close(
        bool $isEmph,
        bool $isStrong,
        array &$openSequence
    ) {
        $end = count($openSequence) -1;

        for ($i = $end; $i >= 0 and ($isEmph or $isStrong); $i--)
        {
            if ($isEmph and ($openSequence[$i] & self::EM))
            {
                $isEmph = false;

                $openSequence[$i] &= ~self::EM;
            }

            if ($isStrong and ($openSequence[$i] & self::ST))
            {
                $isStrong = false;

                $openSequence[$i] &= ~self::ST;
            }
        }

        # slice off any now irrelevant openings
        $openSequence = array_slice($openSequence, 0, $i + 2);

        # clean up if last item was fully closed
        if (end($openSequence) === 0)
        {
            array_pop($openSequence);
        }
    }

    protected function __construct(
        int $width,
        int $textStart,
        string $text
    ) {
        $this->width     = $width;
        $this->textStart = $textStart;

        $this->Element = new InlineElement(($textStart % 2 ? 'em' : 'strong'));

        $this->Element->appendContent($text);
    }
}
