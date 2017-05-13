<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Aidantwoods\Inlines;

use Parsemd\Parsemd\Elements\InlineElement;
use Parsemd\Parsemd\Lines\Line;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Core\Inlines\AbstractInline;

use Parsemd\Parsemd\Parsers\CommonMark\Abstractions\Inlines\Emphasis;

class StrikeThrough extends Emphasis implements Inline
{
    protected const TAG = 'del';

    protected static $markers = [
        '~'
    ];
}
