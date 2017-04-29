<?php

namespace Parsemd\Parsemd\Parsers;

use Parsemd\Parsemd\Parser;
use Parsemd\Parsemd\Lines\Lines;

interface Block extends Parser
{
    /**
     * Determine whether the Block can begin
     *
     * $Lines::current() should be used as a root but the structure
     * may traverse backwards if the marker is not the start of
     * the structure.
     *
     * isPresent MUST use $Lines::jump() or equivalent to leave the
     * pointer in the start position upon exit if it is not in the correct
     * place already.
     *
     * @param Lines $Lines
     *
     * @return bool;
     */
    public static function isPresent(Lines $Lines) : bool;

    /**
     * Will be called upon {@see isPresent} returning `true` with the line
     * pointer in the position left by {@see isPresent}. {@see begin} MUST
     * return either `null` (in case of failure), or a new instance of self.
     *
     * The content at $Lines::current() MUST be parsed (using
     * {@see parse} or otherwise) before the instance of self is returned.
     *
     * Content not found at the current pointer MAY be read, but MUST NOT
     * be parsed.
     *
     * @param Lines $Lines
     *
     * @return static of type matching the current implementation
     */
    public static function begin(Lines $Lines) : Block;

    /**
     * Lines:current() MUST be used as the begining of the structure.
     *
     * {@see parse} MAY use the pointer to estabilish how to parse the item in
     * the current position. However {@see parse} MUST NOT generate parse
     * content for that falls outside the scope of the current line pointer
     *
     * parse must store parsed content internally for later retrieval
     *
     * @param Lines $Lines
     *
     * @return bool
     *  `true` should be returned on success, `false` otherwise
     */
    public function parse(Lines $Lines) : bool;

    /**
     * Determine whether the block has been interrupted
     * at $Lines::current()
     *
     * @param Lines $Lines
     *
     * @return bool
     */
    public function isInterrupted() : bool;

    /**
     * Determine whether the block may continue from
     * $Lines::current()
     *
     * {@see isContinuable} should move the line pointer to the last position
     * in which it is able to continue
     *
     * @param Lines $Lines
     *
     * @return bool
     */
    public function isContinuable(Lines $Lines) : bool;

    /**
     * Interrupt the block at $Lines::current()
     */
    public function interrupt() : void;

    /**
     * Un-interrupt the block at $Lines::current()
     */
    public function uninterrupt() : void;

    /**
     * Return the number of lines the block backtracked during or before
     * construction, zero if none
     *
     * @return int
     */
    public function backtrackCount() : int;

    /**
     * Will be called when the block is considered complete.
     */
    public function complete() : void;
}
