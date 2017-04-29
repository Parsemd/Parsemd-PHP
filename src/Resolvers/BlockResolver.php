<?php

namespace Parsemd\Parsemd\Resolvers;

use Parsemd\Parsemd\Lines\Lines;

use Parsemd\Parsemd\Parsers\{
    Block,
    Core\Blocks\Paragraph
};

use Parsemd\Parsemd\Parsers\CommonMark\Blocks\{
    ListBlock,
    IndentedCode,
    Quote
};

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
