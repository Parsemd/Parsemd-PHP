<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Strictdown\Inlines;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Parsemd\Abstractions\Inlines\Emphasis;

class StrongEmphasis extends Emphasis implements Inline
{
    protected const TAG = 'strong';

    protected const MARKERS = [
        '*'
    ];

    protected const MIN_RUN = 2;
}
