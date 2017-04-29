<?php

namespace Aidantwoods\Parsemd;

use Aidantwoods\Parsemd\Lines\Line;

/**
 * An Inline MUST treat the null character ("\0") as if it were a consumable
 * text character if the Inline allows containment of subparsable structures.
 *
 * E.g. If an element begins with ('^') and ends with ('&') and wishes to
 * contain other structures, then "^foo\0\0\0bar\0&" is of width 12,
 * text start is 1, and text width is 10. The Inline returned by Parse also
 * contains a Line element with text content "foo\0\0\0bar\0".
 */
interface Inline extends Parser
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
     * @return ?static of type matching the current implementation
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
