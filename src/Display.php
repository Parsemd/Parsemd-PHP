<?php

namespace Parsemd\Parsemd;

use Parsemd\Parsemd\Elements\InlineElement;

abstract class Display
{
    public static function elements(
        array $Elements,
        string $indent = ''
    ) : string
    {
        $string = '';

        $subIndent = "  $indent";

        foreach ($Elements as $Element)
        {
            $string .= (empty($indent) ? "\n" : '')
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
                    $string
                        .= $subIndent.str_replace(
                            "\n",
                            "\n$subIndent",
                            $Line
                        )."\n";
                }
            }
            elseif ($Element->getContent()->count() > 0)
            {
                $string
                    .= $subIndent.str_replace(
                        "\n",
                        "\n$subIndent",
                        $Element->getContent()->current()
                    )."\n";
            }

            $string .= self::elements($Element->getElements(), $subIndent);
        }

        return $string;
    }
}
