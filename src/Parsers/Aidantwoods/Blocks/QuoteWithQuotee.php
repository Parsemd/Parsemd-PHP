<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Aidantwoods\Blocks;

use Parsemd\Parsemd\Lines\Lines;
use Parsemd\Parsemd\Lines\Line;
use Parsemd\Parsemd\Elements\BlockElement;

use Parsemd\Parsemd\Parsers\Block;
use Parsemd\Parsemd\Parsers\CommonMark\Blocks\Quote;

class QuoteWithQuotee extends Quote implements Block
{
    protected const MARKERS = [
        '['
    ];

    protected static function isPresent(
        Lines $Lines,
        ?array &$data = null
    ) : bool
    {
        if ($Lines->lookup($Lines->key() + 1) === null)
        {
            return false;
        }

        $NextLines = $Lines->subset($Lines->key() + 1);

        if (parent::isPresent($NextLines))
        {
            $Line = new Line($Lines->current());

            $pos      = [];
            $nextJump = '[';
            $complete = false;

            $Line->strcspnInitJump($nextJump);

            for (; $Line->valid(); $Line->strcspnJump($nextJump))
            {
                if ( ! isset($pos['qOpen']))
                {
                    if ($Line->key() > 4 or $Line->isEscaped())
                    {
                        return false;
                    }
                    else
                    {
                        $pos['qOpen'] = $Line->key() + 1;
                        $nextJump = ']';

                        continue;
                    }
                }

                if ($Line->isEscaped())
                {
                    continue;
                }

                if ( ! isset($pos['qClose']))
                {
                    $pos['qClose'] = $Line->key();

                    if (
                        $Line[1] === ':'
                        and trim($Line->lookup($Line->key() + 2) ?? '') === ''
                    ) {
                        $complete = true;

                        break;
                    }
                    elseif ($Line[1] === '[')
                    {
                        $pos['tOpen'] = $Line->key() + 2;

                        continue;
                    }

                    return false;
                }
                elseif ( ! isset($pos['tClose']))
                {
                    $pos['tClose'] = $Line->key();

                    if (
                        $Line[1] === ':'
                        and trim($Line->lookup($Line->key() + 2) ?? '') === ''
                    ) {
                        $complete = true;

                        break;
                    }

                    return false;
                }
            }

            if ($complete)
            {
                $data['quotee'] = $Line->substr($pos['qOpen'], $pos['qClose']);

                $data['timestamp'] = (
                    isset($pos['tOpen']) ?
                    $Line->substr(
                        $pos['tOpen'],
                        $pos['tClose']
                    )
                    : null
                );

                return true;
            }
        }

        return false;
    }

    public static function begin(Lines $Lines) : ?Block
    {
        if (self::isPresent($Lines, $data))
        {
            return new static($data['quotee'], $data['timestamp']);
        }

        return null;
    }

    private function __construct(string $quotee, ?string $timestamp = null)
    {
        $this->Element = new BlockElement('blockquote');

        $this->Element->setAttribute('data-quotee', $quotee);

        if (isset($timestamp))
        {
            $this->Element->setAttribute('data-timestamp', $timestamp);
        }
    }
}
