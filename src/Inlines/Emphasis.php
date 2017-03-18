<?php

namespace Aidantwoods\Phpmd\Inlines;

use Aidantwoods\Phpmd\Inline;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\InlineElement;

use Aidantwoods\Phpmd\Lines\Line;

class Emphasis extends AbstractInline implements Inline
{
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
        $root     = self::measureDelimiterRun($Line);
        $marker   = $Line->current()[0];
        $offset   = ($root < 3 ? $root : null);

        if ( ! $root or ! self::isLeftFlanking($Line, $root))
        {
            return null;
        }

        $start = $Line->key();
        $Line  = clone($Line);

        $openSequence = array();

        for (; $Line->valid(); $Line->strcspnJump($marker))
        {
            if ($length = self::measureDelimiterRun($Line, $marker))
            {
                $isLf = self::isLeftFlanking($Line, $length);
                $isRf = self::isRightFlanking($Line, $length);

                $nEmph = (bool) ($length % 2);

                if ($nEmph)
                {
                    $nStrong = ($length > 1);
                }
                else
                {
                    $nStrong = true;
                }

                if (
                    $isLf
                    and (
                        ! $isRf
                        or empty($openSequence)
                    )
                ) {
                    if ($nEmph and $nStrong)
                    {
                        $openSequence[] = self::EM | self::ST;
                    }
                    elseif ($nEmph)
                    {
                        $openSequence[] = self::EM;
                    }
                    elseif ($nStrong)
                    {
                        $openSequence[] = self::ST;
                    }
                }
                elseif ($isRf and ( ! $isLf or (($length + $root) % 3)))
                {
                    $end = count($openSequence) -1;

                    for ($i = $end; $i >= 0 and ($nEmph or $nStrong); $i--)
                    {
                        if ($nEmph and ($openSequence[$i] & self::EM))
                        {
                            $nEmph = false;

                            $openSequence[$i] &= ~self::EM;
                        }

                        if ($nStrong and ($openSequence[$i] & self::ST))
                        {
                            $nStrong = false;

                            $openSequence[$i] &= ~self::ST;
                        }
                    }

                    $openSequence = array_slice($openSequence, 0, $i + 2);

                    if ($openSequence[$i + 1] === 0)
                    {
                        array_pop($openSequence);
                    }

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
            if ( ! $offset)
            {
                if ($root % 2)
                {
                    $offset = ($length % 2 ? 1 : 2);
                }
                else
                {
                    $offset = 2;
                }
            }

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
