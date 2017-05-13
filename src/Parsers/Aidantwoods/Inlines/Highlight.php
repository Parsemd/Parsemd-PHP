<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Aidantwoods\Inlines;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Parsemd\Abstractions\Inlines\Emphasis;

class Highlight extends Emphasis implements Inline
{
    protected const TAG = 'mark';

    protected static $markers = [
        '='
    ];
}
