<?php
declare(strict_types=1);

namespace Parsemd\Parsemd;

interface Element
{
    public function __construct(string $type);

    public function getType() : string;

    public function appendContent(string $content) : void;

    public function getContent();

    public function dumpElements() : void;

    public function setAttribute(string $attribute, $value) : void;

    public function getAttributes() : array;

    public function getAttribute(string $name);

    public function setNonReducible(bool $mode = true) : void;

    public function setNonInlinable(bool $mode = true) : void;

    public function isReducible() : bool;

    public function isInlinable() : bool;

    public function __clone();
}
