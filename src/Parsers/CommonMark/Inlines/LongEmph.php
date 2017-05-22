<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Inlines;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Parsemd\Abstractions\Inlines\Emphasis;

class LongEmph extends Emphasis implements Inline
{
    protected const TAG = 'strong';

    protected const MARKERS = [
        '*', '_'
    ];

    protected const INTRAWORD_MARKER_BLACKLIST = [
        '_'
    ];

    protected const MAX_RUN = 2;
    protected const MIN_RUN = 2;
}
