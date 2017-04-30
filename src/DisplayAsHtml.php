<?php

namespace Parsemd\Parsemd;

use Parsemd\Parsemd\Elements\InlineElement;

abstract class DisplayAsHtml
{
    protected const SAFE_SCHEMES = array(
        'http',
        'https',
        'mailto',
        'irc',
        'ircs',
        'git',
        'ssh',
        'ftp',
        'ftps',
        'news',
    );

    protected static function safeSchemesRe() : string
    {
        static $regex;

        if ( ! isset($regex))
        {
            $regex = '/'
                . '\s*+'
                . '(' . implode('|', self::SAFE_SCHEMES) . ')'
                . '\s*+:'
                . '|[\/#]'
                . '/i';
        }

        return $regex;
    }

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

            if ($Element->getType() === 'a')
            {
                self::safeSchemeSanitise($Element, 'href');
            }

            if ($Element->getType() === 'img')
            {
                self::safeSchemeSanitise($Element, 'src');
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
                            $texts[] = "$key=\"".self::escape($value).'"';
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
                    $string .= self::escape($Line);
                }
            }
            elseif ($Element->getContent()->count() > 0)
            {
                $string .= self::escape($Element->getContent()->current());
            }

            $string .= self::elements($Element->getElements());

            if ($Element->getType() !== 'text')
            {
                $string .= '</'.$Element->getType().">";
            }
        }

        return $string;
    }

    protected static function escape(string $string) : string
    {
        return htmlentities($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * If the given $attribute does not match a safe scheme, url encode
     * everything except `/` and `#`
     *
     * @param Element $Element
     * @param string $attribute
     */
    protected static function safeSchemeSanitise(
        Element $Element,
        string  $attribute
    ) {
        if (
            ! preg_match(
                self::safeSchemesRe(),
                $Element->getAttribute($attribute)
            )
        ) {
            $Element->setAttribute(
                $attribute,
                preg_replace_callback(
                    '/[^\/#]++/',
                    function (array $match)
                    {
                        return urlencode($match[0]);
                    },
                    $Element->getAttribute($attribute)
                )
            );
        }
    }
}
