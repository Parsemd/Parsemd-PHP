<?php

namespace Aidantwoods\Phpmd\Lines;

use Iterator;

interface Pointer extends Iterator
{
    /**
     * Move backward to previous position
     */
    public function before();

    /**
     * Jump to the given position
     */
    public function jump(int $position);

    /**
    * @return int the length of pointer validity
    */
    public function count() : int;
}