<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Elements;

use Parsemd\Parsemd\{
    AbstractElement,
    Lines\Line
};

class InlineElement extends AbstractElement
{
    protected $Line,
              $nonNestables = null,
              $reducible    = false,
              $unescape     = true;

    public function __construct(string $type)
    {
        $this->type = $type;

        $this->Line = new Line;
    }

    public function appendContent(string $content) : void
    {
        $this->Line->append($content);
    }

    public function getContent() : Line
    {
        return $this->Line;
    }

    public function appendElement(InlineElement $Element)
    {
        $this->Elements[] = $Element;
    }

    public function setNotUnescapeContent(bool $mode = true)
    {
        $this->unescape = ( ! $mode);
    }

    public function canUnescapeContent() : bool
    {
        return $this->unescape;
    }

    public function setNonNestables(array $nonNestables)
    {
        $this->nonNestables = $nonNestables;
    }

    public function getNonNestables() : ?array
    {
        return $this->nonNestables;
    }

    public function canNest(InlineElement $Element) : bool
    {
        return (
            $this->isInlinable()
            and ! self::isRestricted(
                $this->getNonNestables(),
                $Element
            )
        );
    }

    public function __clone()
    {
        parent::__clone();

        $this->Line = clone($this->Line);
    }

    public static function isRestricted(
        ?array        $restrictions,
        InlineElement $Element
    ) : bool
    {
        $type = strtolower($Element->getType());

        if ( ! empty($restrictions))
        {
            foreach ($restrictions as $restrictedType)
            {
                if ($type === strtolower($restrictedType))
                {
                    return true;
                }
            }
        }

        foreach ($Element->getElements() as $SubElement)
        {
            if (self::isRestricted($restrictions, $SubElement))
            {
                return true;
            }
        }

        return false;
    }
}
