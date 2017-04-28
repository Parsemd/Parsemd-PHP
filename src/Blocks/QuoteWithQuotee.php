<?php

namespace Aidantwoods\Phpmd\Blocks;

use Aidantwoods\Phpmd\Block;
use Aidantwoods\Phpmd\Lines\Lines;
use Aidantwoods\Phpmd\Elements\BlockElement;

class QuoteWithQuotee extends Quote implements Block
{
    protected static $markers = array(
        '['
    );

    public static function isPresent(
        Lines   $Lines,
        ?string &$quotee    = null,
        ?string &$timestamp = null
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
                $quotee    = $matches[1];
                $timestamp = $matches[2] ?? null;

                return true;
            }
        }

        return false;
    }

    public static function begin(Lines $Lines) : Block
    {
        if (self::isPresent($Lines, $quotee, $timestamp))
        {
            return new static($quotee, $timestamp);
        }
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
