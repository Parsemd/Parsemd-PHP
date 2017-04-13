<?php

namespace Aidantwoods\Phpmd;

require_once('vendor/autoload.php');

$text = file_get_contents('markdownText.md');

$Phpmd = new Phpmd;

$t0 = microtime(true);

$Elements = $Phpmd->parse($text);

$t1 = microtime(true);

echo Display::elements($Elements);

echo "\n\n".round(1000*($t1 - $t0), 1)."ms\n";
