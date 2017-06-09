<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Strictdown\Inlines;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Parsemd\Abstractions\Inlines\Emphasis as Emph;

class Emphasis extends Emph implements Inline
{
    protected const TAG = 'em';

    protected const MARKERS = [
        '_'
    ];

    protected const MIN_RUN = 2;
}
