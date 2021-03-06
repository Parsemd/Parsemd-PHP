<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Blocks;

use Parsemd\Parsemd\Lines\Lines;
use Parsemd\Parsemd\Elements\BlockElement;

use Parsemd\Parsemd\Parsers\Block;
use Parsemd\Parsemd\Parsers\Core\Blocks\AbstractBlock;
use Parsemd\Parsemd\Parsers\CommonMark\Blocks\ThematicBreak;

class ListBlock extends AbstractBlock implements Block
{
    private $fullMarker;
    private $CurrentLi;
    private $marker;
    private $initalWhitespace;
    private $requiredIndent;
    private $loose = false;

    protected const MARKERS = [
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        '-', '*', '+'
    ];

    protected static function isPresent(
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
            return new static($Lines, $data);
        }

        return null;
    }

    public function interrupts(Block $Block) : bool
    {
        if (
            array_key_exists(
                'start',
                $this->getElement()->getAttributes()
            )
            and $Block instanceof Paragraph
        ) {
            return $Block->isInterrupted();
        }

        return parent::interrupts($Block);
    }

    public function parse(Lines $Lines) : bool
    {
        if (trim($Lines->current()) === '')
        {
            $this->CurrentLi->appendContent('');

            return true;
        }

        $data = self::deconstructLine($Lines->current());

        if ( ! $data or $this->isContent($Lines))
        {
            $this->unInterrupt();

            $this->CurrentLi->appendContent(
                $Lines->currentLtrimUpto($this->requiredIndent)
            );

            return true;
        }
        elseif ($this->marker === $data['marker'])
        {
            $this->unInterrupt();

            $Li = new BlockElement('li');

            $Li->appendContent($data['text']);

            $this->Element->appendElement($Li);

            $this->CurrentLi = $Li;

            $this->configureWhitespace($data);

            return true;
        }

        return false;
    }

    public function isContinuable(Lines $Lines) : bool
    {
        if (trim($Lines->current()) === '')
        {
            $this->interrupt();

            return true;
        }

        $isPresent = $this->isPresent($Lines, $this->marker);
        $isContent = $this->isContent($Lines);

        if ($isPresent or $isContent)
        {
            return true;
        }
        elseif ( ! $this->isInterrupted())
        {
            $this->interrupt();

            return true;
        }

        return false;
    }

    private function __construct(Lines $Lines, array $data)
    {
        if (strpos('*-+', $data['marker']) !== false)
        {
            $type = 'ul';

            $startNumber = null;
        }
        else
        {
            $type = 'ol';

            $startNumber = substr(
                $data['fullMarker'],
                0,
                strspn($data['fullMarker'], '1234567890')
            );

            if ($startNumber === '1')
            {
                $startNumber = null;
            }
        }

        $this->configureWhitespace($data);

        $Li = new BlockElement('li');

        $Li->appendContent($data['text']);

        $List = new BlockElement($type);

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
        if (ThematicBreak::begin(new Lines($line)))
        {
            return null;
        }
        elseif (
            preg_match(
                '/^([ ]*+)(?|([0-9]{1,9}+([.)]))|(([*+-])))(\s++|$)(.*+)$/',
                $line,
                $matches
            )
        ) {
            if (empty($matches[4]))
            {
                $matches[4] = ' ';
            }

            $matches[4] = Lines::convertTabs($matches[4]);

            if (strlen($matches[4]) > 4)
            {
                $matches[5] = substr($matches[4], 1) . $matches[5];
                $matches[4] = ' ';
            }

            $data = [
                'fullMarker'
                    => $matches[2],
                'marker'
                    => $matches[3],
                'initialWhitespace'
                    => $matches[4],
                'leadWhitespace'
                    => $matches[1],
                'text'
                    => $matches[5]
            ];

            return $data;
        }
        else
        {
            return null;
        }
    }

    private function configureWhitespace(array $data)
    {
        $this->fullMarker = $data['fullMarker'];
        $this->marker     = $data['marker'];

        $this->leadWhitepace = strlen($data['leadWhitespace']);

        if (empty($data['text']))
        {
            $this->initialWhitespace = 1;
        }
        else
        {
            $this->initialWhitespace = strlen($data['initialWhitespace']);
        }

        $this->requiredIndent = $this->leadWhitepace
                              + $this->initialWhitespace
                              + strlen($this->fullMarker);
    }
}
