<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\GitHubFlavor\Blocks;

use Parsemd\Parsemd\Lines\Lines;
use Parsemd\Parsemd\Lines\Line;
use Parsemd\Parsemd\Elements\BlockElement;

use Parsemd\Parsemd\Parsers\Block;
use Parsemd\Parsemd\Parsers\Core\Blocks\AbstractBlock;

class Table extends AbstractBlock implements Block
{
    private $columns;
    private $tBody;

    protected static $markers = [
        '-', '|', ':'
    ];

    public static function begin(Lines $Lines) : ?Block
    {
        if ($data = self::tableMarker($Lines->current()))
        {
            $before = $Lines->lookup($Lines->key() -1) ?? '';
            $cols   = count($data);

            # if we have sufficient headings to match marker indicated columns
            if (count(self::decomposeTableRow($before)) === $cols)
            {
                $headings = self::decomposeTableRow($before);

                return new static($headings, $data);
            }
        }

        return null;
    }

    public function parse(Lines $Lines) : bool
    {
        $texts = self::decomposeTableRow($Lines->current());

        $row = new BlockElement('tr');
        $row->setNonReducible();

        $this->tBody->appendElement($row);

        foreach ($texts as $i => $text)
        {
            if ($i >= $this->columns)
            {
                break;
            }

            $td = new BlockElement('td');
            $td->setNonReducible();

            $td->appendContent(trim($text));

            if ($this->alignmentData[$i]['align'] !== null)
            {
                $td->setAttribute('align', $this->alignmentData[$i]['align']);
            }

            $row->appendElement($td);
        }

        return true;
    }

    public function isContinuable(Lines $Lines) : bool
    {
        return (
            $this->columns <= count(self::decomposeTableRow($Lines->current()))
        );
    }

    public function backtrackCount() : int
    {
        return 1;
    }

    private function __construct(array $headings, array $alignmentData)
    {
        $this->columns = count($headings);

        $this->alignmentData = $alignmentData;

        $this->Element = new BlockElement('table');
        $this->Element->setNonReducible();

        $tHead = new BlockElement('thead');
        $tHead->setNonReducible();

        $row = new BlockElement('tr');
        $row->setNonReducible();

        $tHead->appendElement($row);
        $this->Element->appendElement($tHead);

        foreach ($headings as $i => $heading)
        {
            $th = new BlockElement('th');
            $th->setNonReducible();

            $th->appendContent(trim($heading));

            if ($this->alignmentData[$i]['align'] !== null)
            {
                $th->setAttribute('align', $this->alignmentData[$i]['align']);
            }

            $row->appendElement($th);
        }

        $tBody = new BlockElement('tbody');
        $tBody->setNonReducible();

        $this->tBody = $tBody;

        $this->Element->appendElement($tBody);
    }

    private static function decomposeTableRow(string $line, int $cols = null)
    {
        if (
            preg_match_all(
                '/[|]?+((?:[\\\][|]|[^|])++)[|]?+/',
                $line,
                $matches
            )
        ) {
            if (isset($cols))
            {
                $row = array_slice($matches[1], 0, $cols);
            }
            else
            {
                $row = $matches[1];
            }

            return $row;
        }

        return null;
    }

    private static function tableMarker(string $line) : ?array
    {
        $normalisedLine = str_replace(' ', '', $line);

        $lineWithoutAllowedChars = str_replace(
            [':', '|', '-'],
            '',
            $normalisedLine
        );

        if ($lineWithoutAllowedChars !== '')
        {
            return null;
        }

        $Line = new Line($normalisedLine);

        $data = null;

        for ($Line->rewind(); $Line->valid(); $Line->strcspnJump('|'))
        {
            if ($data === null and $Line[0] !== '|')
            {
                $Line->jump(-1);
            }

            $seek = ($Line[1] === ':' ? 2 : 1);

            if ($Line[$seek] === '-')
            {
                $new = [
                    'align' => ($seek === 1 ? null : 'left')
                ];

                $sepMark   = strcspn($Line->lookup($Line->key() + $seek), '|');
                $alignMark = strcspn($Line->lookup($Line->key() + $seek), ':');

                if ($alignMark < $sepMark -1)
                {
                    return null;
                }
                elseif ($Line[$seek + $sepMark -1] === ':')
                {
                    $new['align'] = (
                        $new['align'] === 'left' ? 'center' : 'right'
                    );
                }

                if ($data === null)
                {
                    $data = [];
                }

                $data[] = $new;
            }
            else
            {
                return null;
            }
        }

        return $data;
    }
}
