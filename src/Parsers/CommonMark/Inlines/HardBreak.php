<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Core\Inlines\AbstractInline;

class HardBreak extends AbstractInline implements Inline
{
    protected const MARKERS = [
        ' ', '\\',
    ];

    public static function parse(Line $Line) : ?Inline
    {
        if ($len = static::measureHardBreak($Line))
        {
            return new static($len);
        }

        return null;
    }

    private function __construct(int $len)
    {
        $this->textStart = 0;

        $this->Element = new InlineElement('br');
        $this->Element->setNonInlinable();

        $this->width = $len;
    }

    private static function measureHardBreak(Line $Line) : int
    {
        $Line = clone($Line);

        if ($Line[0] === '\\' and $Line[1] === "\n")
        {
            $remainder = $Line->substr($Line->key() + 2);

            return 2 + strlen($remainder) - strlen(ltrim($remainder));
        }
        elseif ($Line[0] === ' ' and $Line[1] === ' ')
        {
            $cur = $Line->current();

            $spLen = strspn($cur, ' ');
            $nlOff = strcspn($cur, "\n");

            if ($nlOff === $spLen)
            {
                $remainder = $Line->substr($Line->key() + $nlOff + 1);

                return (
                    1 + $nlOff + strlen($remainder) - strlen(ltrim($remainder))
                );
            }
        }

        return 0;
    }
}
