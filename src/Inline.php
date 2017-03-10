<?php

namespace Aidantwoods\Phpmd;

use Aidantwoods\Phpmd\Lines\Line;

interface Inline extends Structure
{
    /**
     * MUST return either `null` (in case of failure), or a new
     * instance of self after parsing a section of text
     *
     * The entire element MUST be parsed before the instance of self is
     * returned.
     *
     * @param Line $Line
     *
     * @return static of type matching the current implementation
     */
    public static function parse(Line $Line) : ?Inline;

    /**
     * Return the width of the (raw) text parsed by {@see parse}
     *
     * @return int
     */
    public function getWidth() : int;

    /**
     * Return the number of characters preceding the subparsable text parsed
     * by {@see parse}
     *
     * @return int
     */
    public function getTextStart() : int;

    /**
     * Return the width of the subparsable text parsed by {@see parse}
     *
     * @return int
     */
    public function getTextWidth() : int;
}
