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
     *  distinct multiples of two, so this number may this be used
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

    protected static function parseText(Line $Line) : ?array
    {
        # data from the begining of the text
        $root     = self::measureDelimiterRun($Line);
        $marker   = $Line->current()[0];
        $start    = $Line->key();

        # ensure there is a root delimiter and it is left flanking
        if ( ! $root or ! self::isLeftFlanking($Line, $root))
        {
            return null;
        }

        # we may be able to determine our emphasis type now
        $offset = ($root < 3 ? $root : ($root % 2 ? null : self::ST));

        # make a copy of the Line object
        $Line = clone($Line);

        # we will need to record nested structures so we can ignore their
        # endings
        $openSequence = array();

        for (; $Line->valid(); $Line->strcspnJump($marker))
        {
            # if the current position is a delimiter run
            if ($length = self::measureDelimiterRun($Line, $marker))
            {
                # are we left or right flanking?
                $isLf = self::isLeftFlanking($Line, $length);
                $isRf = self::isRightFlanking($Line, $length);

                # an emph, a strong emph, or both
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

                # jump to the end of the current delimiter run
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

        if (preg_match('/^(['.$marker.'])\1*+/', $Line->current(), $match))
        {
            if ($Line->lookup($Line->key() -1)[0] === $match[1])
            {
                return null;
            }

            $length = strlen($match[0]);

            $before = $Line->lookup($Line->key() -1) ?? ' ';
            $after  = $Line->lookup($Line->key() + $length) ?? ' ';

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

        if ($isEmph)
        {
            $isStrong = ($length > 1);
        }
        else
        {
            $isStrong = true;
        }

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
        return (
            $isLf
            and (
                ! $isRf
                or empty($openSequence)
            )
        );
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
        if ($isEmph and $isStrong)
        {
            $openSequence[] = self::EM | self::ST;
        }
        elseif ($isEmph)
        {
            $openSequence[] = self::EM;
        }
        elseif ($isStrong)
        {
            $openSequence[] = self::ST;
        }
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

        $openSequence = array_slice($openSequence, 0, $i + 2);

        if ($openSequence[$i + 1] === 0)
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
