<?php
declare(strict_types=1);

namespace Parsemd\Parsemd;

/**
 * When two Inlines intersect in a non-nestable way, their Resolver will be
 * used to determine which takes priority.
 *
 * First, $Current will be asked if it ignores $Next (i.e. should $Current be
 * picked in favour of $Next without asking $Next's opinion?).
 * If $Current wishes to ignore $Next, $Current will take priority.
 *
 * Otherwise, if $Current does not invoke its right to oppress, $Next will be
 * asked if it interrupts $Current (i.e. should $Next be picked in favour
 * of $Current?).
 * If $Next wishes to interrupt $Current then $Next will take priority.
 *
 * Otherwise, $Current will take priority.
 */
interface Resolvable
{
    /**
     * Determine whether $Next interrupts $Current.
     *
     * On call may assume that $Next is of type static.
     *
     * @param InlineData $Current
     * @param InlineData $Next
     *
     * @return bool
     */
    public function interrupts(InlineData $Current, InlineData $Next) : bool;

    /**
     * Determine whether $Current ignores $Next.
     *
     * On call may assume that $Current is of type static.
     *
     * @param InlineData $Current
     * @param InlineData $Next
     *
     * @return bool
     */
    public function ignores(InlineData $Current, InlineData $Next) : bool;
}
