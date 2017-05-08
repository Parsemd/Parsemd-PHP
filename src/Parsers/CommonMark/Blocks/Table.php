<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Blocks;

use Parsemd\Parsemd\{
    Lines\Lines,
    Elements\BlockElement
};

use Parsemd\Parsemd\Parsers\{
    Block,
    Core\Blocks\AbstractBlock
};

class Table extends AbstractBlock implements Block
{
    private $columns,
            $tBody;

    protected static $markers = array(
        '-', '|'
    );

    public static function begin(Lines $Lines) : ?Block
    {
        if ($cols = self::tableMarker($Lines->current()))
        {
            $before = $Lines->lookup($Lines->key() -1) ?? '';

            # if we have sufficient headings to match marker indicated columns
            if (count(self::decomposeTableRow($before)) === $cols)
            {
                $headings = self::decomposeTableRow($before);

                return new static($headings);
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

            $data = new BlockElement('td');
            $data->setNonReducible();

            $data->appendContent(trim($text));

            $row->appendElement($data);
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

    private function __construct(array $headings)
    {
        $this->columns = count($headings);

        $this->Element = new BlockElement('table');
        $this->Element->setNonReducible();

        $tHead = new BlockElement('thead');
        $tHead->setNonReducible();

        $row = new BlockElement('tr');
        $row->setNonReducible();

        $tHead->appendElement($row);
        $this->Element->appendElement($tHead);

        foreach ($headings as $heading)
        {
            $data = new BlockElement('td');
            $data->setNonReducible();

            $data->appendContent(trim($heading));

            $row->appendElement($data);
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

    private static function tableMarker(string $line) : ?int
    {
        $normalisedLine = str_replace(' ', '', $line);

        if (
            preg_match(
                '/^[ ]{0,3}+[|]?+(([-]++)(?:[|](?1))?)[|]?+$/',
                $normalisedLine,
                $matches
            )
        ) {
            # count the column dividers (after ignoring outer dividers in
            # the regex for group 1)
            return substr_count($matches[1], '|') + 1;
        }

        return null;
    }
}
