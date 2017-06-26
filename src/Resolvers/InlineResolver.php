<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Resolvers;

use Parsemd\Parsemd\InlineData;
use Parsemd\Parsemd\InlineExtender;
use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Elements\InlineElement;

use Parsemd\Parsemd\Parsers\CommonMark\Inlines\Code;
use Parsemd\Parsemd\Parsers\CommonMark\Inlines\Link;
use Parsemd\Parsemd\Parsers\Parsemd\Abstractions\Inlines\Emphasis;
use Parsemd\Parsemd\Parsers\CommonMark\Inlines\Image;

abstract class InlineResolver
{
    /**
     * The following describes a recursive algorithm which takes an ordered
     * collection of parsed Inlines (coupled with positional metadata), and
     * returns a compatible collection of non-intersecting Inlines (coupled
     * with positional metadata).
     *
     * ---
     *
     * Let InlineData be an Inline coupled with its positional metadata.
     * Let Inlines be an ordered list by start position where each item is an
     *     InlineData.
     * Let Resolved be a an empty list which may be populated with InlineData
     * items.
     *
     * Examine all InlineDatas in Inlines by repeating the following
     * bullet points until InlineDatas is empty.
     *
     * * Let Current be the InlineData in Inlines
     *
     * * Make a copy of Current and remove it from Inlines
     *
     * * Let Interrupts be an empty list which may be populated with InlineData
     *
     * * Let Next be null
     *
     * * Foreach Inlines as Intersect:
     *   * If Intersect is {@see intersecting} Current
     *     AND Intersect is (not contained in a parseable subsection of Current
     *     OR may not be contained in Current)
     *     AND is able to interrupt Current,
     *     then:
     *     * Let Interrupts be the result of recursing this entire algorithm
     *       over all Inlines after and including Intersect.
     *     * Break the current loop over Inlines
     *    Otherwise:
     *    * If Next is null, let Next be Intersect
     *
     * * Discard from Interrupts, all InlineData which does not intersect the
     *   former
     *
     * * If Interrupts is empty:
     *   * Append Current to Resolved and discard from Inlines all InlineData
     *     which intersect the former
     *   Otherwise:
     *   * If Next is null, let Next be the first item in Interrupts
     *   * Foreach Interrupts as Interrupt:
     *     * Let Pair be an ordered list containing Next and Interrupt
     *       respectively.
     *     * If Current may be extended over the text spanned by InlineData
     *       in Pair then:
     *       * Let New be the result of this extension
     *       * Push New into the first position in Inlines
     *       * Break the current loop over Interrupts
     *
     * Return Resolved
     *
     * ---
     *
     * @param InlineData[] $Inlines
     *
     * @return array Resolved
     */
    public static function resolve(array $Inlines) : array
    {
        $Resolved = [];

        while ( ! empty($Inlines))
        {
            $Current    = array_shift($Inlines);
            $Interrupts = [];
            $Next       = null;

            if ($Interrupts = self::resolve($Inlines))
            {
                while (
                    ! empty($Interrupts)
                    and ! self::canInterrupt($Current, reset($Interrupts))
                ) {
                    array_shift($Interrupts);
                }

                $Interrupts = self::filterIntersecting($Current, $Interrupts);
            }

            if (empty($Interrupts))
            {
                $Resolved[] = $Current;

                $Inlines = self::filterNotIntersecting($Current, $Inlines);
            }
            else
            {
                $Next = $Next ?? reset($Inlines);

                if (
                    ! $Current->getInline()->getElement()->canNest(
                        $Next->getInline()->getElement()
                    )
                ) {
                    continue;
                }

                foreach ($Interrupts as $Interrupt)
                {
                    if (
                        ! $Current->getInline()->getElement()->canNest(
                            $Interrupt->getInline()->getElement()
                        )
                    ) {
                        break;
                    }

                    $Pair = [$Next, $Interrupt];

                    if ($New = InlineExtender::extend($Current, $Pair))
                    {
                        array_unshift($Inlines, $New);

                        break;
                    }
                }
            }
        }

        return $Resolved;
    }

    /**
     * Determine extensively whether $Next may interrupt $Current, (based on
     * positional critera as well as precedence rules).
     *
     * When two Inlines intersect in a non-nestable way, their Resolver will be
     * used to determine which takes priority.
     *
     * First, $Current will be asked if it ignores $Next (i.e. should $Current
     * be picked in favour of $Next without asking $Next's opinion?).
     * If $Current wishes to ignore $Next, $Current will take priority.
     *
     * Otherwise, if $Current does not invoke its right to oppress, $Next will
     * be asked if it interrupts $Current (i.e. should $Next be picked in favour
     * of $Current?).
     * If $Next wishes to interrupt $Current then $Next will take priority.
     *
     * Otherwise, $Current will take priority.
     *
     * @param InlineData $Current
     * @param InlineData $Next
     *
     * @return bool
     */
    public static function canInterrupt(
        InlineData $Current,
        InlineData $Next
    ) : bool
    {
        return (
            ! self::isInSubparseableSubsection($Current, $Next)
            and ! $Current->getInline()->ignores($Current, $Next)
            and $Next->getInline()->interrupts($Current, $Next)
        );
    }

    private static function isInSubparseableSubsection(
        InlineData $Current,
        InlineData $Next
    ) : bool
    {
        return (
            $Next->start() < $Current->end()
            and (
                $Next->start() >= $Current->textStart()
                and $Next->end() <= $Current->textEnd()
                and $Current->getInline()->getElement()->canNest(
                    $Next->getInline()->getElement()
                )
            )
        );
    }

    // private static function fromIntersecting(
    //     InlineData $current,
    //     array $Inlines
    // ) : array {
    //     for (
    //         $i = 0;
    //         isset($Inlines[$i]) and self::intersects($current, $Inlines[$i]);
    //         $i++
    //     ) {
    //         return array_slice($Inlines, $i);
    //     }
    // }

    private static function intersects(
        InlineData $Current,
        InlineData $Next
    ) : bool
    {
        return $Next->start() <= $Current->end();
    }

    private static function filterIntersecting(
        InlineData $Current,
        array      $Inlines
    ) : array
    {
        return array_filter(
            $Inlines,
            function (InlineData $Next) use ($Current)
            {
                return self::intersects($Current, $Next);
            }
        );
    }

    private static function filterNotIntersecting(
        InlineData $Current,
        array      $Inlines
    ) : array
    {
        return array_filter(
            $Inlines,
            function (InlineData $Next) use ($Current)
            {
                return ! self::intersects($Current, $Next);
            }
        );
    }
}
