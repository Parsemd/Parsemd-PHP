<?php
declare(strict_types=1);

namespace Parsemd\Parsemd;

use Parsemd\Parsemd\Elements\InlineElement;
use Aidantwoods\Sets\Sets\StringSet;

abstract class DisplayAsHtml
{
    protected const SAFE_SCHEMES = [
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
    ];

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

    public static function reduceInlinesToText(array $Elements) : string
    {
        $text = '';

        foreach ($Elements as $Element)
        {
            if ($Element->getContent()->count() > 0)
            {
                $text .= (string) $Element->getContent();
            }

            $text .= self::reduceInlinesToText($Element->getElements());
        }

        return $text;
    }

    public static function elements(array $Elements) : string
    {
        $string = '';

        $n = count($Elements);

        foreach ($Elements as $i => $Element)
        {
            if ($Element->getType() === 'hr' or $Element->getType() === 'br')
            {
                $string .= '<'.$Element->getType().' />'.($i < $n -1 ? "\n" : '');
                continue;
            }

            if ($Element->getType() === 'a')
            {
                self::safeSchemeSanitise($Element, 'href');
            }

            if ($Element->getType() === 'img')
            {
                self::safeSchemeSanitise($Element, 'src');
                $Element->setAttribute('alt', self::reduceInlinesToText($Element->getElements()));
                $Element->dumpElements();
                $Element->setAttribute('title', $Element->getAttribute('title'));
            }

            if ($Element->getType() !== 'text')
            {
                $string .= '<'.$Element->getType();

                if ($Element->getType()[0] !== 'h')
                {
                    $string .= (function () use ($Element)
                    {
                        $texts = [];

                        foreach ($Element->getAttributes() as $key => $value)
                        {
                            if ( ! isset($value))
                            {
                                continue;
                            }

                            $texts[] = "$key=\"".self::escape($value).'"';
                        }

                        return (empty($texts) ? '' : ' '.implode(' ', $texts));
                    })();
                }

                $string .= ($Element->getType() === 'img' ? ' /' : '').'>';
            }

            if ( ! $Element instanceof InlineElement)
            {
                $string .= self::escape((string) $Element->getContent(), true);
            }
            elseif ($Element->getContent()->count() > 0)
            {
                $string .= self::escape(
                    (string) $Element->getContent(),
                    true
                );
            }

            $string .= self::elements($Element->getElements());

            if ( ! in_array($Element->getType(), ['text', 'img'], true))
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

            if ($Element->getAttribute('title') !== null)
            {
                $Element->setAttribute('title', $Element->getAttribute('title'));
            }
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
