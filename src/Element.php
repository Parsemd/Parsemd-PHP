<?php

namespace Aidantwoods\Phpmd;

interface Element
{
    public function __construct(string $type);

    public function getType() : string;

    public function appendContent(string $content);

    public function getContent();

    public function dumpElements();

    public function setAttribute(string $attribute, $value);

    public function getAttributes() : array;

    public function setNonReducible(bool $mode = true);

    public function setNonInlinable(bool $mode = true);

    public function isReducible() : bool;

    public function isInlinable() : bool;

    public function __clone();
}
