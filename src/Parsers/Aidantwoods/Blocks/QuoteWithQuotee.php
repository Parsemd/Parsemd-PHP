<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Aidantwoods\Blocks;

use Parsemd\Parsemd\{
    Lines\Lines,
    Elements\BlockElement
};

use Parsemd\Parsemd\Parsers\{
    Block,
    CommonMark\Blocks\Quote
};

class QuoteWithQuotee extends Quote implements Block
{
    protected static $markers = array(
        '['
    );

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
            if (
                preg_match(
                    '/^[ ]{0,3}(?:\[([^]]++)\])(?:\[([^]]++)\])?:/',
                    $Lines->current(),
                    $matches
                )
            ) {
                $data['quotee']    = $matches[1];
                $data['timestamp'] = $matches[2] ?? null;

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
