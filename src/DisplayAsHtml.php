<?php

namespace Parsemd\Parsemd;

use Parsemd\Parsemd\Elements\InlineElement;

abstract class DisplayAsHtml
{
    public static function elements(array $Elements) : string
    {
        $string = '';

        foreach ($Elements as $Element)
        {
            if ($Element->getType() === 'hr')
            {
                $string .= '<hr />';
                continue;
            }

            if ($Element->getType() !== 'text')
            {
                $string .= '<'.$Element->getType();

                if ($Element->getType()[0] !== 'h')
                {
                    $string .= (function () use ($Element)
                    {
                        $texts = array();

                        foreach ($Element->getAttributes() as $key => $value)
                        {
                            $texts[] = "$key=\"$value\"";
                        }

                        return (empty($texts) ? '' : ' '.implode(' ', $texts));
                    })();
                }

                $string .= '>';
            }

            if ( ! $Element instanceof InlineElement)
            {
                foreach ($Element->getContent() as $Line)
                {
                    $string .= $Line;
                }
            }
            elseif ($Element->getContent()->count() > 0)
            {
                $string .= $Element->getContent()->current();
            }

            $string .= self::elements($Element->getElements());

            if ($Element->getType() !== 'text')
            {
                $string .= '</'.$Element->getType().">";
            }
        }

        return $string;
    }
}
