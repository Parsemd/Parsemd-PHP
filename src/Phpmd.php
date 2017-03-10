<?php
# this file is a testing environment that ties the application together and is
# very much incomplete and too dense
namespace Aidantwoods\Phpmd;

use Aidantwoods\Phpmd\Lines\Line;
use Aidantwoods\Phpmd\Lines\Lines;

use Aidantwoods\Phpmd\Blocks\Paragraph;
use Aidantwoods\Phpmd\Inlines\Text;

class Phpmd
{
    private $BlockHandlers = array(
        'Aidantwoods\Phpmd\Blocks\PreCode',
        'Aidantwoods\Phpmd\Blocks\Heading',
        'Aidantwoods\Phpmd\Blocks\ListBlock',
        'Aidantwoods\Phpmd\Blocks\ThematicBreak',
        'Aidantwoods\Phpmd\Blocks\Table'
    );

    private $InlineHandlers = array(
        'Aidantwoods\Phpmd\Inlines\Code',
        'Aidantwoods\Phpmd\Inlines\Link'
    );

    private $BlockMarkerRegister = array();
    private $InlineMarkerRegister = array();

    private function registerBlockHandlers()
    {
        foreach ($this->BlockHandlers as $Block)
        {
            foreach ($Block::getMarkers() as $marker)
            {
                if ( ! array_key_exists($marker, $this->BlockMarkerRegister))
                {
                    $this->BlockMarkerRegister[$marker] = array();
                }

                $this->BlockMarkerRegister[$marker][] = $Block;
            }
        }
    }

    private function registerInlineHandlers()
    {
        foreach ($this->InlineHandlers as $Inline)
        {
            foreach ($Inline::getMarkers() as $marker)
            {
                if ( ! array_key_exists($marker, $this->InlineMarkerRegister))
                {
                    $this->InlineMarkerRegister[$marker] = array();
                }

                $this->InlineMarkerRegister[$marker][] = $Inline;
            }
        }
    }

    private function findNewBlock(string $marker, Lines $Lines) : Block
    {
        if (array_key_exists($marker, $this->BlockMarkerRegister))
        {
            foreach ($this->BlockMarkerRegister[$marker] as $handler)
            {
                if ($handler::isPresent($Lines))
                {
                    $Block = $handler::begin($Lines);

                    return $Block;
                }
            }
        }

        return Paragraph::begin($Lines);
    }

    private function findNewInline(string $marker, Line $Line) : ?Inline
    {
        if (array_key_exists($marker, $this->InlineMarkerRegister))
        {
            foreach ($this->InlineMarkerRegister[$marker] as $handler)
            {
                if ($Inline = $handler::parse($Line))
                {
                    return $Inline;
                }
            }
        }

        return null;
    }

    private function findBlockMarker(Lines $Lines) : ?string
    {
        $marker = $Lines->current()[0] ?? null;

        if ( ! isset($marker) or $marker === ' ')
        {
            $trimmedLine = preg_replace(
                '/^[ ]{0,3}+(?=[^ ])/',
                '',
                $Lines->current()
            );

            $newMarker = $trimmedLine[0] ?? $marker;

            if ($newMarker !== $marker)
            {
                $marker = $newMarker;
            }
        }

        return $marker;
    }

    private function parseInlines(
        Line $Line,
        ?array $restrictions = null
    ) : array
    {
        $Elements = array();

        $Inlines = array();

        $mask = implode('', array_keys($this->InlineMarkerRegister));

        $restrictions = $restrictions ?? array();

        for ($Line->rewind(); $Line->valid(); $Line->strcspnJump($mask))
        {
            $marker = $Line->current()[0];

            $Inline = $this->findNewInline($marker, $Line);

            if ( ! empty($restrictions))
            {
                foreach ($restrictions as $class)
                {
                    if (is_subclass_of($Inline, $class))
                    {
                        unset($Inline);

                        continue 2;
                    }
                }
            }

            if (isset($Inline))
            {
                $Inlines[] = array(
                    'start'
                        => $Line->key(),
                    'end'
                        => $Line->key() + $Inline->getWidth(),
                    'textEnd'
                        => $Line->key() + $Inline->getTextStart() + $Inline->getTextWidth(),
                    'textStart'
                        => $Line->key() + $Inline->getTextStart(),
                    'inline'
                        => $Inline
                );

                continue;
            }
        }

        $Inlines = InlineResolver::resolve($Inlines);

        $blank = 0;

        foreach ($Inlines as $data)
        {
            if ($data['start'] > $blank)
            {
                $text = $Line->subset($blank, $data['start']);

                $text = Text::parse($text);

                $Elements[] = $text->getElement();
            }

            $blank = $data['end'];

            $Elements[] = $data['inline']->getElement();
        }

        if ($Line->count() > $blank)
        {
            $text = $Line->subset($blank, $Line->count());

            $text = Text::parse($text);

            $Elements[] = $text->getElement();
        }

        return $Elements;
    }

    private function parseLines(Lines $Lines) : array
    {
        $Elements = array();

        for ($Lines->rewind(); $Lines->valid(); $Lines->next())
        {
            $marker = $this->findBlockMarker($Lines);

            if (isset($marker))
            {
                $NewBlock = $this->findNewBlock($marker, $Lines);
            }
            else
            {
                unset($NewBlock);
            }

            if (isset($Block))
            {
                if (
                    $Block->isContinuable($Lines)
                    and (
                        ! isset($NewBlock)
                        or ! Resolver::interrupts($NewBlock, $Block, $Lines)
                    )
                ) {
                    $Block->parse($Lines);

                    continue;
                }

                $lineRef = &$Lines->currentRef();
                $lineRef = ltrim($lineRef);

                # This allows the new block to backtrack into lines already
                # parsed by the current block
                # We must reparse the lines for the current block
                # so that lines are not parsed twice by different block
                # structures
                if (isset($NewBlock) and $NewBlock->backtrackCount() > 0)
                {
                    $position = $Lines->key() - $NewBlock->backtrackCount();

                    $Elements = array_merge(
                        $Elements,
                        $this->parseLines(
                            $Lines->subset($blockStart, $position)
                        )
                    );
                }
                else
                {
                    $Elements[] = $Block->getElement();
                }

                unset($Block);

                if (isset($NewBlock))
                {
                    $blockStart = $Lines->key();

                    $Block = $NewBlock;
                }
                else
                {
                    $Lines->before();
                }

                continue;
            }
            elseif (isset($NewBlock))
            {
                $blockStart = $Lines->key();

                $Block = $NewBlock;

                continue;
            }
        }

        if (isset($Block))
        {
            $Elements[] = $Block->getElement();
        }

        return $Elements;
    }

    private function reduceElement(Element $Element)
    {
        $Content = $Element->getContent()->pop();

        $Element->appendElements($this->elements($Content));

        foreach ($Element->getElements() as $SubElement)
        {
            if ($SubElement->isReducible())
            {
                $this->reduceElement($SubElement);
            }
        }
    }

    private function inlineElement(
        Element $Element,
        ?array $restrictions = null
    ) {
        if ( ! $Element instanceof InlineElement)
        {
            $Lines = $Element->getContent()->pop();

            $Lines->rewind();

            if ($Lines->valid())
            {
                $Line = new Line($Lines->current());

                for ($Lines->jump(1); $Lines->valid(); $Lines->next())
                {
                    $Line->append("\n".$Lines->current());
                }

                $Element->appendElements(
                    $this->inlineLine($Line, $restrictions)
                );
            }
        }
        else
        {
            $Content = $Element->getContent()->pop();

            $subRestrictions = $Element->getNonNestables();

            if ( ! empty($subRestrictions))
            {
                $restrictions = array_merge(
                    $restrictions ?? array(),
                    $subRestrictions
                );
            }

            $Element->appendElements(
                $this->inlineLine($Content, $restrictions)
            );
        }

        foreach ($Element->getElements() as $SubElement)
        {
            if ($SubElement->isInlinable())
            {
                $this->inlineElement($SubElement);
            }
        }
    }

    private function inlineLine(
        Line $Line,
        ?array $restrictions = null
    ) : array
    {
        $Elements = $this->parseInlines($Line, $restrictions);

        foreach ($Elements as $Element)
        {
            if ($Element->isInlinable())
            {
                $this->inlineElement($Element);
            }
        }

        return $Elements;
    }

    private function elements(Lines $Lines) : array
    {
        $Elements = $this->parseLines($Lines);

        foreach ($Elements as $Element)
        {
            if ($Element->isReducible())
            {
                $this->reduceElement($Element);
            }

            if ($Element->isInlinable())
            {
                $this->inlineElement($Element);
            }
        }

        return $Elements;
    }

    public function __construct()
    {
        $this->registerBlockHandlers();
        $this->registerInlineHandlers();
    }

    public function parse(string $text) : array
    {
        $Lines = new Lines(str_replace("\0", '', $text));

        return $this->elements($Lines);
    }

}
