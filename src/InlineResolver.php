<?php

namespace Aidantwoods\Phpmd;

use Aidantwoods\Phpmd\Lines\Line;

use Aidantwoods\Phpmd\Inlines\Code;
use Aidantwoods\Phpmd\Inlines\Link;

abstract class InlineResolver
{
    public static function interrupts(
        Inline $NewInline,
        Inline $Inline
    ) {
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

    public static function resolve(array $Inlines) : array
    {
        $Resolved = array();

        while ( ! empty($Inlines))
        {
            $current = $Inlines[0];
            $interrupts = array();

            $i = 1;

            while (
                isset($Inlines[$i])
                and $Inlines[$i]['start'] < $current['end']
            ) {
                if (
                    ! (
                        $Inlines[$i]['start'] >= $current['textStart']
                        and $Inlines[$i]['end'] <= $current['textEnd']
                    )
                    and self::interrupts(
                        $Inlines[$i]['inline'],
                        $current['inline'])
                ) {
                    $interrupts = array_merge(
                        $interrupts, self::resolve(array_slice($Inlines, $i))
                    );
                }
                else
                {
                    unset($Inlines[$i]);
                }

                $i++;
            }

            foreach ($interrupts as $key => $interrupt)
            {
                if ($interrupt['start'] > $current['textEnd'])
                {
                    foreach ($Inlines as $k2 => $Inline)
                    {
                        if ($Inline['start'] === $interrupt['start'])
                        {
                            unset($Inlines[$k2]);
                        }
                    }

                    unset($interrupts[$key]);
                }
            }

            if (empty($interrupts))
            {
                $Resolved[] = $current;
            }

            array_shift($Inlines);
        }

        return $Resolved;
    }
}
