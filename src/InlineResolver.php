<?php

namespace Aidantwoods\Phpmd;

use Aidantwoods\Phpmd\Lines\Line;

use Aidantwoods\Phpmd\Inlines\Code;
use Aidantwoods\Phpmd\Inlines\Link;

abstract class InlineResolver
{
    /**
     * Determine whether $NewInline may interrupt $Inline
     *
     * @param Inline $NewInline
     * @param Inline $Inline
     *
     * @return bool
     */
    public static function interrupts(
        Inline $NewInline,
        Inline $Inline
    ) : bool
    {
        if ($NewInline instanceof Code and ! $Inline instanceof Code)
        {
            return true;
        }

        if ($NewInline instanceof Link and $Inline instanceof Link)
        {
            return true;
        }

        return false;
    }

    /**
     * Let InlineData be an Inline coupled with its positional metadata.
     * Let Inlines be a list where each item is an InlineData.
     * Let Resolved be a an empty list which may be populated with InlineData
     * items.
     *
     * Examine all InlineDatas in Inlines by repeating the following
     * bullet points until InlineDatas is empty.
     *
     * * Let the former be the InlineData which holds the lowest start
     *   position, and let the latter the next InlineData
     *
     * * Copy former and remove it from Inlines
     *
     * * Let Interrupts be an empty list which may be populated with InlineData
     *
     * * Determine whether the latter is:
     *   1. Intersecting the former; and
     *   2. Not contained in a parseable subsection of the former; and
     *   3. Able to interrupt the former
     *
     * * If all of the above hold true then:
     *   Recurse this algorithm for all InlineDatas occurring after former,
     *   and place the Resolved returned from this recursion into Interrupts
     *
     * * Discard from Interrupts, all InlineData which does not intersect the
     *   former
     *
     * * If Interrupts is empty, place the former onto the end of Resolved and
     *   discard from Inlines all InlineData which intersect the former
     *
     * Return Resolved
     *
     * @param array $Inlines
     *
     * @return array Resolved
     */
    public static function resolve(array $Inlines) : array
    {
        $Resolved = array();

        while ( ! empty($Inlines))
        {
            $current = array_shift($Inlines);
            $interrupts = array();

            if (
                isset($Inlines[0])
                and $Inlines[0]['start'] < $current['end']
                and ! (
                    $Inlines[0]['start'] >= $current['textStart']
                    and $Inlines[0]['end'] <= $current['textEnd']
                    and self::canNest(
                        $current['inline']->getElement(),
                        $Inlines[0]['inline']->getElement()
                    )
                )
                and self::interrupts(
                    $Inlines[0]['inline'],
                    $current['inline']
                )
            ) {
                $interrupts = self::resolve($Inlines);
            }

            foreach ($interrupts as $key => $interrupt)
            {
                if ($interrupt['start'] > $current['end'])
                {
                    unset($interrupts[$key]);
                }
            }

            if (empty($interrupts))
            {
                $Resolved[] = $current;

                for (
                    $i = 0;
                    isset($Inlines[$i])
                    and $Inlines[$i]['start'] < $current['end'];
                    $i++
                ) {
                    unset($Inlines[$i]);
                }
            }
        }

        return $Resolved;
    }

    public static function canNest(
        InlineElement $Outer,
        InlineElement $Inner
    ) : bool
    {
        return (
            $Outer->isInlinable()
            and ! self::isRestricted(
                $Outer->getNonNestables(),
                $Inner
            )
        );
    }

    public static function isRestricted(
        array $restrictions,
        InlineElement $Element
    ) : bool
    {
        $type = strtolower($Element->getType());

        if ( ! empty($restrictions))
        {
            foreach ($restrictions as $restrictedType)
            {
                if ($type === strtolower($restrictedType))
                {
                    return true;
                }
            }
        }

        foreach ($Element->getElements() as $SubElement)
        {
            if (self::isRestricted($restrictions, $SubElement))
            {
                return true;
            }
        }

        return false;
    }
}
