<?php

namespace Aidantwoods\Parsemd\Resolvers;

use Aidantwoods\Parsemd\Block;

use Aidantwoods\Parsemd\Blocks\Paragraph;
use Aidantwoods\Parsemd\Blocks\ListBlock;
use Aidantwoods\Parsemd\Blocks\IndentedCode;
use Aidantwoods\Parsemd\Blocks\Quote;

use Aidantwoods\Parsemd\Lines\Lines;

abstract class BlockResolver
{
    public static function interrupts(Block $NewBlock, Block $Block) : bool
    {
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
            $Block instanceof Quote
            and ! $NewBlock instanceof Paragraph
            and ! $NewBlock instanceof IndentedCode
            and ! $NewBlock instanceof Quote
        ) {
            return true;
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
