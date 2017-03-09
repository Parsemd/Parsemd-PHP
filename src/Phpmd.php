<?php

namespace Aidantwoods\Phpmd;

require_once(__DIR__.'/../vendor/autoload.php');

include('../parsedown/Parsedown.php');

use Aidantwoods\Phpmd\Lines\Line;
use Aidantwoods\Phpmd\Lines\Lines;

use Aidantwoods\Phpmd\Blocks\Paragraph;
use Aidantwoods\Phpmd\Inlines\Text;

function elementsEcho(array $Elements, string $indent = '')
{
    $subIndent = "  $indent";

    foreach ($Elements as $Element)
    {
        echo (empty($indent) ? "\n" : '')
            .$indent.$Element->getType().
            (function () use ($Element)
            {
                $texts = array();

                foreach ($Element->getAttributes() as $key => $value)
                {
                    $texts[] = "$key=\"$value\"";
                }

                return (empty($texts) ? '' : ' '.implode(' ', $texts));
            })()
            .":\n";

        if ( ! $Element instanceof InlineElement)
        {
            foreach ($Element->getContent() as $Line)
            {
                echo $subIndent.$Line."\n";
            }
        }
        else
        {
            echo $subIndent.$Element->getContent()->current()."\n";
        }

        elementsEcho($Element->getElements(), $subIndent);
    }
}

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
        'Aidantwoods\Phpmd\Inlines\Code'
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

    private function findNewBlock(string $marker, Lines $Lines) : ?Block
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
        ?string $mask = null,
        ?array $restrictions = null
    ) : array
    {
        $Elements = array();

        $mask = $mask ?? implode('', array_keys($this->InlineMarkerRegister));

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

                        break;
                    }
                }
            }

            if ( ! isset($Inline))
            {
                $textStart = $Line->key();

                continue;
            }
            else
            {
                if (isset($textStart) and $textStart < $Line->key())
                {
                    $text = Text::parse(
                        $Line->subset($textStart, $Line->key())
                    );

                    $Elements[] = $text->getElement();

                    unset($textStart, $text);
                }
                else
                {
                    unset($textStart);
                }

                $Elements[] = $Inline->getElement();

                $Line->jump($Line->key() + $Inline->getWidth());

                $textStart = $Line->key();

                continue;
            }
        }

        if (isset($textStart) and $textStart < $Line->key())
        {
            $text = Text::parse(
                $Line->subset($textStart, $Line->key())
            );

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
        ?string $mask = null,
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
                    $this->inlineLine($Line, $mask, $restrictions)
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
                $this->inlineLine($Content, $mask, $restrictions)
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
        ?string $mask = null,
        ?array $restrictions = null
    ) : array
    {
        $Elements = $this->parseInlines($Line, $mask, $restrictions);

        foreach ($Elements as $Element)
        {
            if ($Element->isInlinable())
            {
                $this->inlineElement($Element);
            }
        }

        return $Elements;
    }

    private function squashInlines(Element $Element, array $squashes) : array
    {
        $squashed = array();

        $Elements = $Element->getElements();

        $Element->dumpElements();

        foreach ($Elements as $SubElement)
        {
            if (
                $SubElement instanceof InlineElement
                and ! $SubElement->isInlinable()
            ) {
                if ( ! in_array($SubElement->getType(), $squashes))
                {
                    $Element->appendContent(
                        $SubElement->getContent()->pop()->current(),
                        true,
                        false
                    );
                }
                else
                {
                    $Element->appendContent("\0", true, false);

                    $squashed[] = $SubElement;
                }
            }
            elseif ($SubElement->isInlinable())
            {
                foreach ($SubElement->getElements() as $SubSubElement)
                {
                    $squashed = array_merge(
                        $squashed,
                        $this->squashInlines($SubSubElement, $squashes)
                    );
                }
            }
        }

        return $squashed;
    }

    private function unsquashInlines(Element $Element, array &$squashed)
    {
        if (empty($squashed))
        {
            return;
        }

        $Elements = $Element->getElements();

        $Element->dumpElements();

        foreach ($Elements as $SubElement)
        {
            if ($SubElement instanceof InlineElement)
            {
                $content = $SubElement->getContent()->pop();

                while (
                    $content->valid()
                    and ($n = strpos($content->current(), "\0")) !== false
                ) {
                    $text = new InlineElement('text');

                    $text->appendContent($content->subset(0, $n)->current());

                    $content = $content->subset($n + 1, $content->count());

                    $Element->appendElement($text);

                    $Element->appendElement(array_shift($squashed));
                }

                if ($content->valid())
                {
                    $text = new InlineElement('text');

                    $text->appendContent($content->current());

                    $Element->appendElement($text);
                }
            }
            else
            {
                $Element->appendElement($SubElement);

                $this->unsquashInlines($SubElement, $squashed);
            }
        }

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
                # we must first parse inline code elements because they
                # take special precedence over all other inline elements

                $this->inlineElement($Element, '`');

                # next we squash these structures into their parents
                # reperesenting the inline code structures by ordered
                # null characters (these are disallowed in markdown
                # so no danger of collision)

                $squashed = $this->squashInlines($Element, ['code']);

                # next we inline the remaining elements

                $this->inlineElement($Element);

                # now unsquash the null characters back into inline code

                $this->unsquashInlines($Element, $squashed);
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

$text = file_get_contents(
    '/Users/Aidan/GitHub/SecureHeaders/docs/generated/apply.md'
);

// var_dump($Lines);

$Phpmd = new Phpmd();

$t2 = microtime(true);

$Elements = $Phpmd->parse($text);

$t3 = microtime(true);

elementsEcho($Elements);

// var_dump($Elements);

$Parsedown = new \Parsedown;

$t0 = microtime(true);

$out = $Parsedown->text($text);

$t1 = microtime(true);

echo "\n\n".round(1000*($t1 - $t0), 1)."ms";

echo "\n".round(1000*($t3 - $t2), 1)."ms\n";
