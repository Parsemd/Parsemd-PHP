<?php

namespace Parsemd\Parsemd;

require_once(__DIR__.'/../vendor/autoload.php');

$Parsemd = new Parsemd;

$file    = file_get_contents(__DIR__.'/../markdownText.md');
$rawData = explode("\n\n", $file);
$dir     = __DIR__.'/data';

@mkdir($dir);
@`rm -r $dir/* &> /dev/null`;

foreach ($rawData as $n => $data)
{
    $Elements = $Parsemd->parse($data);

    file_put_contents("$dir/$n.md", $data);
    file_put_contents("$dir/$n.md.txt", Display::elements($Elements));
}
