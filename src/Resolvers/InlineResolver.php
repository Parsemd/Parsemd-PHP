<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Resolvers;

use Parsemd\Parsemd\{
    InlineData,
    Parsers\Inline,
    Elements\InlineElement
};

use Parsemd\Parsemd\Parsers\CommonMark\Inlines\{
    Code,
    Link,
    Emphasis,
    Image
};

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

        if ($NewInline instanceof Image and $Inline instanceof Image)
        {
            return true;
        }

        if ($NewInline instanceof Image and $Inline instanceof Link)
        {
            return true;
        }

        if ($NewInline instanceof Link and $Inline instanceof Image)
        {
            return false;
        }

        /**
         * http://spec.commonmark.org/0.27/#link-text
         * Links may not contain other links, at any level of nesting. If
         * multiple otherwise valid link definitions appear nested inside each
         * other, the inner-most definition is used.
         */
        if ($NewInline instanceof Link and $Inline instanceof Link)
        {
            return true;
        }

        /**
         * http://spec.commonmark.org/0.27/#link-text
         * The brackets in link text bind more tightly than markers for
         * emphasis and strong emphasis.
         */
        if ($NewInline instanceof Link and $Inline instanceof Emphasis)
        {
            return true;
        }

        return false;
    }

    /**
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
     * @param InlineData[] $Inlines
     *
     * @return array Resolved
     */
    public static function resolve(array $Inlines) : array
    {
        $Resolved = array();

        while ( ! empty($Inlines))
        {
            $Current    = array_shift($Inlines);
            $Interrupts = array();
            $Next       = null;

            foreach (self::intersecting($Current, $Inlines) as $i => $Inline)
            {
                if (self::canInterrupt($Current, $Inline))
                {
                    $Interrupts = self::resolve(array_slice($Inlines, $i));

                    break;
                }

                if ($Inline->start() >= $Current->textStart())
                {
                    $Next = $Next ?? $Inline;
                }
            }

            $Interrupts = self::filterIntersecting($Current, $Interrupts);

            if (empty($Interrupts))
            {
                $Resolved[] = $Current;

                $Inlines = self::filterNotIntersecting($Current, $Inlines);
            }
            else
            {
                $Next = $Next ?? reset($Interrupts);

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

                    $Pair = array($Next, $Interrupt);

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
     * positional critera as well as precedence rules)
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
            self::isInSubparseableSubsection($Current, $Next)
            and self::interrupts(
                $Next->getInline(),
                $Current->getInline()
            )
        );
    }

    private static function isInSubparseableSubsection(
        InlineData $Current,
        InlineData $Next
    ) : bool
    {
        return (
            $Next->start() < $Current->end()
            and ! (
                $Next->start() >= $Current->textStart()
                and $Next->end() <= $Current->textEnd()
                and $Current->getInline()->getElement()->canNest(
                    $Next->getInline()->getElement()
                )
            )
        );
    }

    private static function intersecting(InlineData $current, array $Inlines)
    {
        for (
            $i = 0;
            isset($Inlines[$i]) and self::intersects($current, $Inlines[$i]);
            $i++
        ) {
            yield $i => $Inlines[$i];
        }
    }

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
