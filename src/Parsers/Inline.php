<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers;

use Parsemd\Parsemd\Parser;
use Parsemd\Parsemd\Resolvable;
use Parsemd\Parsemd\Lines\Line;

/**
 * The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT",
 * "SHOULD", "SHOULD NOT", "RECOMMENDED",  "MAY", and "OPTIONAL" in
 * this document are to be interpreted as described in RFC 2119.
 * https://tools.ietf.org/html/rfc2119
 *
 * ---
 *
 * An Inline MUST treat the null character ("\0") as if it were a consumable
 * text character if the Inline allows containment of subparsable structures.
 *
 * E.g. If an element begins with ('^') and ends with ('&') and wishes to
 * contain other structures, then "^foo\0\0\0bar\0&" is of width 12,
 * text start is 1, and text width is 10. The Inline returned by Parse also
 * contains a Line element with text content "foo\0\0\0bar\0".
 */
interface Inline extends Parser, Resolvable
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
     * Return the number of characters forward from which the structure wondered
     * from the start position relative to the line pointer when given to
     * {@see parse}
     *
     * @return int
     */
    public function getStart() : int;

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
