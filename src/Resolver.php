<?php

namespace Aidantwoods\Phpmd;

use Aidantwoods\Phpmd\Blocks\Paragraph;
use Aidantwoods\Phpmd\Blocks\ListBlock;

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

        if ( ! $NewBlock instanceof Paragraph and $Block instanceof Paragraph)
        {
            return true;
        }

        return false;
    }
}
