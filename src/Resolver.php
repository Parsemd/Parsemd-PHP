<?php

namespace Aidantwoods\Phpmd;

use Aidantwoods\Phpmd\Blocks\Paragraph;
use Aidantwoods\Phpmd\Blocks\ListBlock;
use Aidantwoods\Phpmd\Blocks\IndentedCode;

use Aidantwoods\Phpmd\Lines\Lines;

abstract class Resolver
{
    public static function interrupts(
        Block $NewBlock,
        Block $Block,
        Lines $Lines
    ) {
        if (
            $NewBlock instanceof ListBlock
            and array_key_exists(
                'start',
                $NewBlock->getElement()->getAttributes()
            )
            and $Block instanceof Paragraph
        ) {
            return $Block->isInterrupted();
        }

        if (
            ! $NewBlock instanceof Paragraph
            and ! $NewBlock instanceof IndentedCode
        ) {
            if ($Block instanceof Paragraph)
            {
                return true;
            }
            elseif ( ! $NewBlock instanceof $Block)
            {
                return $Block->isInterrupted();
            }
        }

        return false;
    }
}
