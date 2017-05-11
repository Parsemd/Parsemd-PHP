<?php
declare(strict_types=1);

namespace Parsemd\Parsemd;

use Parsemd\Parsemd\Elements\InlineElement;
use Aidantwoods\Sets\Sets\StringSet;

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
        'steam',
    );

    protected static function safeSchemes() : StringSet
    {
        static $StringSet;

        if ( ! isset($StringSet))
        {
            $StringSet = new StringSet;
            $StringSet->addArray(self::SAFE_SCHEMES);
        }

        return $StringSet;
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
                    $string .= self::escape($Line, true);
                }
            }
            elseif ($Element->getContent()->count() > 0)
            {
                $string .= self::escape(
                    $Element->getContent()->current(),
                    true
                );
            }

            $string .= self::elements($Element->getElements());

            if ($Element->getType() !== 'text')
            {
                $string .= '</'.$Element->getType().">";
            }
        }

        return $string;
    }

    protected static function escape(
        string $string,
        $allowQuotes = false
    ) : string
    {
        return htmlentities(
            $string,
            $allowQuotes ? ENT_NOQUOTES : ENT_QUOTES,
            'UTF-8'
        );
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
            ! self::strsiStart(
                $Element->getAttribute($attribute),
                self::safeSchemes()
            )
        ) {
            $Element->setAttribute(
                $attribute,
                preg_replace_callback(
                    '/[^\/#?&=%]++/',
                    function (array $match)
                    {
                        return urlencode($match[0]);
                    },
                    $Element->getAttribute($attribute)
                )
            );
        }
    }

    protected static function strsiStart(
        string    $string,
        StringSet $searches
    ) : bool
    {
        $stringLen = strlen($string);

        foreach ($searches as $search)
        {
            $searchLen = strlen($search);

            if ($searchLen > $stringLen)
            {
                return false;
            }
            else
            {
                return (
                    strtolower(substr($string, 0, $searchLen))
                    === strtolower($search)
                );
            }
        }
    }
}
