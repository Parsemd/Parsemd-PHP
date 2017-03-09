<?php

namespace Aidantwoods\Phpmd;

use Aidantwoods\Phpmd\Lines\Line;

/**
 * An Inline MUST NOT consume the null character ("\0") in any non-inlinable
 * content.
 * Content is non-inlinable if it is consumed by {@see parse} but is not moved
 * to an inlinable element's content.
 *
 * An Inline MUST NOT insert additional null characters. Those which are
 * already within a text body MUST be preserved, and MUST NOT be duplicated or
 * removed.
 *
 * If an Inline does not which sub-structures to be inserted within its text,
 * the occurrence of a null MUST cause a parse to be aborted.
 */
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
}
