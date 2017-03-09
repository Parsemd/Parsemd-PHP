<?php

namespace Aidantwoods\Phpmd\Blocks;

use Aidantwoods\Phpmd\Block;
use Aidantwoods\Phpmd\Element;
use Aidantwoods\Phpmd\Structure;
use Aidantwoods\Phpmd\Lines\Lines;

class ListBlock extends AbstractBlock implements Block
{
    private $fullMarker,
            $CurrentLi,
            $marker,
            $initalWhitespace,
            $requiredIndent,
            $Element,
            $loose = false;

    protected static $markers = array(
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        '-', '*'
    );

    public static function isPresent(
        Lines $Lines,
        ?string $marker = null
    ) : bool
    {
        if ($data = self::deconstructLine($Lines->current()))
        {
            if ( ! $marker)
            {
                return true;
            }

            return ($marker === $data['marker']);
        }

        return false;
    }

    public static function begin(Lines $Lines) : ?Block
    {
        if ($data = self::deconstructLine($Lines->current()))
        {
            return new static(
                $Lines,
                $data['fullMarker'],
                $data['marker'],
                $data['initialWhitespace'],
                $data['text']
            );
        }

        return null;
    }

    public function parse(Lines $Lines) : bool
    {
        if ($data = self::deconstructLine($Lines->current()))
        {
            $Li = new Element('li');

            $Li->appendContent($data['text']);

            $this->Element->appendElement($Li);

            $this->CurrentLi = $Li;

            return true;
        }
        elseif ($this->isContent($Lines))
        {
            $trim = preg_replace(
                '/^[ ]{0,'.$this->requiredIndent.'}+/',
                '',
                $Lines->current()
            );

            $previousBreak = (trim($Lines->lookup($Lines->key() -1)) === '');

            if ($previousBreak)
            {
                $this->CurrentLi->appendContent('');
            }

            $this->CurrentLi->appendContent($trim);

            return true;
        }

        return false;
    }

    public function isContinuable(Lines $Lines) : bool
    {
        $lineLength = strlen($Lines->current());

        if ($lineLength === 0)
        {
            $this->interrupt();

            return true;
        }

        if ($this->isContent($Lines))
        {
            return true;
        }
        elseif ($this->isPresent($Lines, $this->marker))
        {
            return true;
        }

        return false;
    }

    public function getElement() : Element
    {
        return $this->Element;
    }

    private function __construct(
        Lines $Lines,
        string $fullMarker,
        string $marker,
        string $initialWhitespace,
        string $text
    ) {
        $this->fullMarker = $fullMarker;

        $this->marker = $marker;

        if ($marker === '*' or $marker === '-')
        {
            $type = 'ul';

            $startNumber = null;
        }
        else
        {
            $type = 'ol';

            $startNumber = preg_replace(
                '/[0-9]++\K.*+/',
                '',
                $fullMarker
            );

            if ($startNumber === '1')
            {
                $startNumber = null;
            }
        }

        if (empty($text))
        {
            $this->initialWhitespace = 1;
        }
        else
        {
            $this->initialWhitespace = strlen($initialWhitespace);
        }

        $this->requiredIndent = $this->initialWhitespace
                              + strlen($this->fullMarker);

        $Li = new Element('li');

        $Li->appendContent($text);

        $List = new Element($type);

        if (isset($startNumber))
        {
            $List->setAttribute('start', $startNumber);
        }

        $List->appendElement($Li);

        $this->Element = $List;

        $this->CurrentLi = $Li;
    }

    private function isContent(Lines $Lines) : bool
    {
        $ltrimLength = strlen(ltrim($Lines->current()));

        $indent = strlen($Lines->current()) - $ltrimLength;

        # if we are sufficiently indented then we are contained in the list

        return ($indent >= $this->requiredIndent);
    }

    private static function deconstructLine(string $line) : ?array
    {
        if (
            preg_match(
                '/^(?|([0-9]{1,9}+([.)]))|(([*-])))([ ]++|$)(.*+)$/',
                $line,
                $matches
            )
        ) {
            if (empty($text))
            {
                $matches[3] = ' ';
            }

            $data = array(
                'fullMarker'
                    => $matches[1],
                'marker'
                    => $matches[2],
                'initialWhitespace'
                    => $matches[3],
                'text'
                    => $matches[4]
            );

            return $data;
        }
        else
        {
            return null;
        }
    }
}
