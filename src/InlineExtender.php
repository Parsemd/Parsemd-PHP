<?php
declare(strict_types=1);

namespace Parsemd\Parsemd;

use Parsemd\Parsemd\Lines\Line;
use Parsemd\Parsemd\Parsers\Inline;

abstract class InlineExtender
{
    private static function squashInlines(array $Inlines) : Line
    {
        $NewLine = new Line;

        $start = $Inlines[0]->start();
        $end   = $Inlines[1]->end();
        $Line  = $Inlines[0]->getLine();

        $NewLine->append($Line->substr(0, $start));
        $NewLine->append(str_repeat("\0", $end - $start));
        $NewLine->append($Line->substr($end));

        return $NewLine;
    }

    private static function unsquashInlines(
        array  $Inlines,
        Inline $Outer
    ) : ?Inline
    {
        $Outer = clone($Outer);

        $text = (string) $Outer->getElement()->getContent();

        $start = $Inlines[0]->start();
        $end   = $Inlines[1]->end();
        $Line  = $Inlines[0]->getLine();

        $n = strpos ($text, "\0");
        $l = strrpos($text, "\0");

        if ($n === false)
        {
            return null;
        }

        $Outer->getElement()->getContent()->pop();

        $newText  = substr($text, 0, $n);
        $newText .= $Line->substr($start, $end);

        if ($l + 1 < strlen($text))
        {
            $newText .= substr($text, $l + 1);
        }

        $Outer->getElement()->appendContent($newText);

        return $Outer;
    }

    public static function extend(
        InlineData $InlineData,
        array      $Interrupts
    ) : ?InlineData
    {
        $squashedLine = self::squashInlines($Interrupts);
        $squashedLine->jump($InlineData->start());

        if ($Inline = $InlineData->getInline()::parse($squashedLine))
        {
            if ($Inline = self::unsquashInlines($Interrupts, $Inline))
            {
                return new InlineData($InlineData->getLine(), $Inline);
            }
        }

        return null;
    }
}
