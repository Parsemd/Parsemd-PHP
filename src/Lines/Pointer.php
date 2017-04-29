<?php

namespace Aidantwoods\Parsemd\Lines;

use Iterator;

interface Pointer extends Iterator
{
    /**
     * Move backward to previous position
     */
    public function before() : void;

    /**
     * Jump to the given position
     */
    public function jump(int $position) : void;

    /**
    * @return int the length of pointer validity
    */
    public function count() : int;
}
