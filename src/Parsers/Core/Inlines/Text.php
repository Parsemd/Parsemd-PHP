<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Core\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Core\Inlines\AbstractInline;

class Text extends AbstractInline implements Inline
{
    protected const MARKERS = [];

    public static function parse(Line $Line) : ?Inline
    {
        return new static($Line->current());
    }

    private function __construct(string $text)
    {
        $this->textStart = 0;

        $this->Element = new InlineElement('text');
        $this->Element->setNonInlinable();

        $this->width = strlen($text);

        # chop out spaces surrounding newlines

        $p = strcspn($text, "\n");
        $n = strlen($text);

        for (; $p < $n; $p += strcspn($text, "\n", $p + 1))
        {
            $bck = $fwd = 0;

            if ($p > 0 and $text[$p -1] === ' ')
            {
                $bck = strspn(strrev($text), ' ', $n - $p + 1);
            }

            if ($p + 1 < $n and $text[$p + 1] === ' ')
            {
                $fwd = strspn($text, ' ', $p + 1);
            }

            $text = substr_replace($text, '', $p - $bck, $bck + $fwd);
            $n    = strlen($text);
            $p    = $p - $bck + 1;
        }

        $this->Element->appendContent($text);
    }
}
