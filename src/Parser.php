<?php

namespace Aidantwoods\Phpmd;

use Aidantwoods\Phpmd\Lines\LineIterator;

/**
 * **Abstract Interface**: This MUST NOT be implemented directly
 */
interface Parser
{
    /**
     * Return an array of markers for which the structure should
     * use to identify itself.
     *
     * @return string[];
     */
    public static function getMarkers() : array;

    /**
     * Retreive a parsed member Element object
     *
     * @return Element
     */
    public function getElement() : Element;
}
