# Parsemd [![Build Status](https://travis-ci.org/Parsemd/Parsemd.svg?branch=master)](https://travis-ci.org/Parsemd/Parsemd) [![Build Status](https://ci.appveyor.com/api/projects/status/github/parsemd/parsemd?branch=master&svg=true&retina=true)](https://ci.appveyor.com/project/aidantwoods/parsemd)
A markdown parser, lovingly crafted in PHP 7.1

## Aims
* Be CommonMark compliant by default
* Make adding custom structures (blocks/inlines)/rules easy and modular
* Decouple HTML output from understanding of the markdown structure (aiming for output in HTML, LaTeX, and beyond)

## Caveats
* Under inital development: (issues welcome), PRs encouraged
* I might move things around drastically (see under initial development)

## FAQ
- Q: Why u no < PHP 7.1 ?! ![y-u-no-guy](https://cloud.githubusercontent.com/assets/3288888/25992650/26c732d4-36ff-11e7-8f0d-a701c9858a94.jpg)
- A: Just as PHP 7 is a much better language than 5.x, 7.1 comes with a few of its [own improvements](http://php.net/manual/en/migration71.new-features.php). In particual nullable return types are made use of extensively in this project. This is achievable in versions prior, but only by forgoing type safety, or by introducing boilerplate code to check functions return what you expect them to. This task is much better achieved by use of an interface, and affords the same advantages to any modular additions.

## Examples
Emphasis and strong emphasis are by far the most complex inline structures.
One of the aims of this parser is to have lots of reusable code.

Here we have an abstraction of emphasis (like a CommonMark emphasis, except
number of delimiters in a delimiter run is arbitrary – like inline code).

This is everything we need to define a new type of emphais, strikethrough.
```php
<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\Aidantwoods\Inlines;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Parsemd\Abstractions\Inlines\Emphasis;

class StrikeThrough extends Emphasis implements Inline
{
    protected const TAG = 'del';
    protected const MARKERS = [
        '~'
    ];
}
```

Infact, the CommonMark emphasis implementation too extends this abstract
idea. Though some adjustments have to be made to separate the `*` delimiter from
the `**` delimiter, and so-forth for `_`.