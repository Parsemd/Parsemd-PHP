<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Inlines;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Parsemd\Abstractions\Inlines\Emphasis;

class ShortEmph extends Emphasis implements Inline
{
    protected const TAG = 'em';

    protected const MARKERS = [
        '*', '_'
    ];

    protected const INTRAWORD_MARKER_BLACKLIST = [
        '_'
    ];

    protected const MAX_RUN = 1;
    protected const MIN_RUN = 1;
}
