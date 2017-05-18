<?php
declare(strict_types=1);
# this file is a testing environment that ties the application together and is
# very much incomplete and too dense
namespace Parsemd\Parsemd;

use Parsemd\Parsemd\Lines\Line;
use Parsemd\Parsemd\Lines\Lines;

use Parsemd\Parsemd\Resolvers\BlockResolver;
use Parsemd\Parsemd\Resolvers\InlineResolver;

use Parsemd\Parsemd\Parsers\Block;
use Parsemd\Parsemd\Parsers\Inline;

use Parsemd\Parsemd\Parsers\Core\Blocks\Paragraph;
use Parsemd\Parsemd\Parsers\Core\Inlines\Text;

use Parsemd\Parsemd\Elements\InlineElement;

class Parsemd
{
    private $BlockHandlers = [
        'Parsemd\Parsemd\Parsers\CommonMark\Blocks\PreCode',
        'Parsemd\Parsemd\Parsers\CommonMark\Blocks\Heading',
        'Parsemd\Parsemd\Parsers\CommonMark\Blocks\ListBlock',
        'Parsemd\Parsemd\Parsers\CommonMark\Blocks\ThematicBreak',
        'Parsemd\Parsemd\Parsers\GitHubFlavor\Blocks\Table',
        'Parsemd\Parsemd\Parsers\CommonMark\Blocks\IndentedCode',
        'Parsemd\Parsemd\Parsers\CommonMark\Blocks\Quote',
        'Parsemd\Parsemd\Parsers\Aidantwoods\Blocks\QuoteWithQuotee',
    ];

    private $InlineHandlers = [
        'Parsemd\Parsemd\Parsers\CommonMark\Inlines\Code',
        'Parsemd\Parsemd\Parsers\CommonMark\Inlines\Link',
        'Parsemd\Parsemd\Parsers\CommonMark\Inlines\Emphasis',
        'Parsemd\Parsemd\Parsers\CommonMark\Inlines\AutoLink',
        'Parsemd\Parsemd\Parsers\CommonMark\Inlines\HardBreak',
        'Parsemd\Parsemd\Parsers\GitHubFlavor\Inlines\AutoLink',
        'Parsemd\Parsemd\Parsers\CommonMark\Inlines\Image',
        'Parsemd\Parsemd\Parsers\Aidantwoods\Inlines\StrikeThrough',
        'Parsemd\Parsemd\Parsers\Aidantwoods\Inlines\Highlight',
    ];

    private $BlockMarkerRegister  = [];
    private $InlineMarkerRegister = [];

    private function registerHandlers()
    {
        $handlerTypes = ['Block', 'Inline'];

        foreach ($handlerTypes as $type)
        {
            $this->registerHandlersOfType($type);
        }
    }

    private function registerHandlersOfType(string $type)
    {
        $markerRegister = "${type}MarkerRegister";

        foreach ($this->{"${type}Handlers"} as $Handler)
        {
            foreach ($Handler::getMarkers() as $marker)
            {
                if ( ! array_key_exists($marker, $this->$markerRegister))
                {
                    $this->$markerRegister[$marker] = [];
                }

                $this->$markerRegister[$marker][] = $Handler;
            }
        }
    }

    private function findNewBlock(string $marker, Lines $Lines) : ?Block
    {
        if (array_key_exists($marker, $this->BlockMarkerRegister))
        {
            foreach ($this->BlockMarkerRegister[$marker] as $handler)
            {
                if ($Block = $handler::begin($Lines))
                {
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
                '/^[ ]{0,3}+/',
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
        Line   $Line,
        ?array $restrictions = null
    ) : array
    {
        $Inlines = [];

        $mask = implode('', array_keys($this->InlineMarkerRegister));

        $restrictions = $restrictions ?? [];

        for ($Line->rewind(); $Line->valid(); $Line->strcspnJump($mask))
        {
            $marker = $Line->current()[0];

            $Inline = $this->findNewInline($marker, $Line);

            if ( ! isset($Inline))
            {
                continue;
            }

            if (
                ! InlineElement::isRestricted(
                    $restrictions,
                    $Inline->getElement()
                )
            ) {
                $newInline = new InlineData($Line, $Inline);

                $Inlines[] = $newInline;
            }
        }

        # filter into coherent collection of compatible Inlines
        $Inlines = InlineResolver::resolve($Inlines);

        # the position of the last blank (text position not covered by an
        # Inline)
        $blank = 0;

        for ($i = 0; $i < count($Inlines); $i++)
        {
            $Data = $Inlines[$i];
            # fill text between $blank and the next Inline start with
            # a Core\Text Inline
            if ($Data->start() > $blank)
            {
                $Line->jump($blank);

                $str = $Line->subset($blank, $Data->start());

                $Text = Text::parse($str);
                $newData = new InlineData($Line, $Text);

                array_splice($Inlines, $i, 0, [$newData]);

                $blank = $newData->end();
            }
            else
            {
                $blank = $Data->end();
            }
        }

        # fill in trailing text with a Core\Text inlines

        for ($Line->jump($blank); $Line->valid(); $Line->jump($blank))
        {
            $str = $Line->subset($blank);

            $Text = Text::parse($str);

            array_push($Inlines, new InlineData($Line, $Text));

            $blank = end($Inlines)->end();
        }

        $Elements = array_map(
            function ($Inline)
            {
                return $Inline->getInline()->getElement();
            },
            $Inlines
        );

        return $Elements;
    }

    private function parseLines(Lines $Lines) : array
    {
        $Elements = [];

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
                        or ! BlockResolver::interrupts($NewBlock, $Block)
                    )
                ) {
                    if ($Block->parse($Lines))
                    {
                        continue;
                    }
                }

                /**
                 * This allows the new block to backtrack into lines already
                 * parsed by the current block
                 * We must reparse the lines for the current block
                 * so that lines are not parsed twice by different block
                 * structures
                 */
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
                    $Block->complete();

                    $Elements[] = $Block->getElement();
                }

                unset($Block);

                if (isset($NewBlock))
                {
                    $blockStart = $Lines->key();

                    $Block = $NewBlock;
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
            $Block->complete();

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
        ?array  $restrictions = null
    ) {
        if ( ! $Element instanceof InlineElement)
        {
            $Lines = $Element->getContent()->pop();

            $Line = new Line((string) $Lines);

            $Element->appendElements($this->inlineLine($Line, $restrictions));
        }
        else
        {
            $Content = $Element->getContent()->pop();

            $subRestrictions = $Element->getNonNestables();

            if ( ! empty($subRestrictions))
            {
                $restrictions = array_merge(
                    $restrictions ?? [],
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
        Line   $Line,
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

    public function __construct(
        ?array $BlockHandlers = null,
        ?array $InlineHandlers = null
    ) {
        $this->BlockHandlers  = $BlockHandlers  ?? $this->BlockHandlers;
        $this->InlineHandlers = $InlineHandlers ?? $this->InlineHandlers;

        $this->registerHandlers();
    }

    public function parse(string $text) : array
    {
        $Lines = new Lines($text);

        return $this->elements($Lines);
    }

    public function html(string $text)
    {
        return DisplayAsHtml::elements($this->parse($text));
    }
}
