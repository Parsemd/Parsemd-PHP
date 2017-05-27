# Parsemd [![Build Status](https://travis-ci.org/Parsemd/Parsemd.svg?branch=master)](https://travis-ci.org/Parsemd/Parsemd) [![Build Status](https://ci.appveyor.com/api/projects/status/github/parsemd/parsemd?branch=master&svg=true&retina=true)](https://ci.appveyor.com/project/aidantwoods/parsemd)
A modular parser for CommonMark, GitHub-Flavoured-Markdown, and beyond. Lovingly crafted in PHP 7.1.

## Aims
One of the main objectives for this parser is creating a system where CommonMark, GitHub-Flavoured-Markdown, and [insert your own dialects here] can co-exist and specific inline/block elements from each can be used in a modular fashion.

*(there will be a YAML file config for authors to use to piece this selection together eventually).*

Another objective is usability, and with that comes a variety of things, including but not limited to:
* Accuracy of the built in dialects(/flavours) to their specifications
* A variety of output formats, creating abstract implementations of common structures (so that slight modifications e.g. particular marker used, how many are used is trivial to adjust)
* Creating a general purpose parsing strategy such that implementations of specific inlines/blocks do not have to be aware of each other, or of how they should cope with various types of intersections. (e.g. inline elements that can be extended to avoid intersection will be without the inline implementation having to be aware that this is possible).


## Aims (tl;dr)
* Be CommonMark compliant by default
* Make adding custom structures (blocks/inlines)/rules easy and modular
* Decouple HTML output from understanding of the markdown structure (aiming for output in HTML, LaTeX, and beyond)

## Caveats
* Under initial development: (issues welcome), PRs encouraged
* I might move things around drastically (see under initial development)

## FAQ
- Q: Why u no < PHP 7.1 ?! ![y-u-no-guy](https://cloud.githubusercontent.com/assets/3288888/25992650/26c732d4-36ff-11e7-8f0d-a701c9858a94.jpg)
- A: Just as PHP 7 is a much better language than 5.x, 7.1 comes with a few of its [own improvements](http://php.net/manual/en/migration71.new-features.php). In particular, nullable return types are made use of extensively in this project. This is achievable in versions prior, but only by forgoing type safety, or by introducing boilerplate code to check functions return what you expect them to. This task is much better achieved by use of an interface, and affords the same advantages to any modular additions.

## Examples
Emphasis and strong emphasis are by far the most complex inline structures.
One of the aims of this parser is to have lots of reusable code.

Here we have an abstraction of emphasis (like a CommonMark emphasis, except
number of delimiters in a delimiter run is arbitrary – like inline code).

This is everything we need to define a new type of emphasis, strikethrough.
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

That implementation is only slightly longer though. Here it is.
```php
<?php
declare(strict_types=1);

namespace Parsemd\Parsemd\Parsers\CommonMark\Inlines;

use Parsemd\Parsemd\Parsers\Inline;
use Parsemd\Parsemd\Parsers\Parsemd\Abstractions\Inlines\Emphasis;

class ShortEmph extends Emphasis implements Inline
{
    protected const TAG = 'em';

    protected const MARKERS = [
        '*', '_'
    ];

    protected const INTRAWORD_MARKER_BLACKLIST = [
        '_'
    ];

    protected const MAX_RUN = 1;
    protected const MIN_RUN = 1;
}
```
