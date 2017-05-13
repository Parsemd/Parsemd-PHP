<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Aidantwoods\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Core\Inlines\AbstractInline;

use Parsemd\Parsemd\Parsers\CommonMark\Inlines\AbstractEmphasis;

class Highlight extends AbstractEmphasis implements Inline
{
    protected const TAG = 'mark';

    protected static $markers = [
        '='
    ];
}
