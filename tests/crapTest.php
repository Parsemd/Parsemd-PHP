<?php

use Parsemd\Parsemd\Parsemd;
use Parsemd\Parsemd\Display;

use PHPUnit\Framework\TestCase;

class CrapTest extends TestCase
{
    protected $Parsemd;

    protected function setUp()
    {
        $this->Parsemd = new Parsemd;
    }

    /**
     * @dataProvider data
     * @param $markdown
     * @param $expected
     */
    public function test($markdown, $expected)
    {
        $Elements = $this->Parsemd->parse($markdown);

        $this->assertSame(Display::elements($Elements), $expected);
    }

    /**
     * @return array
     */
    public function data() : array
    {
        $contents = scandir(__DIR__.'/data');

        $contents = array_map(
            function ($item)
            {
                return __DIR__."/data/$item";
            },
            $contents
        );

        $contents = array_reduce(
            $contents,
            function ($carry, $item)
            {
                if (is_file($item))
                {
                    $carry[] = $item;
                }

                return $carry;
            },
            []
        );

        $dataMeta = array_reduce(
            $contents,
            function ($carry, $item)
            {
                if (substr($item, -3) === '.md')
                {
                    $carry[$item]['markdown'] = $item;
                }
                elseif (substr($item, -4) === '.txt')
                {
                    $carry[substr($item, 0, -4)]['expected'] = $item;
                }

                return $carry;
            },
            []
        );

        $data = array_map(
            function ($meta)
            {
                if (isset($meta['markdown']) and isset($meta['expected']))
                {
                    return [
                        'markdown' => file_get_contents($meta['markdown']),
                        'expected' => file_get_contents($meta['expected'])
                    ];
                }
                else
                {
                    echo "Test data mismatch\n";
                    var_dump($meta);
                    exit(1);
                }
            },
            $dataMeta
        );

        return $data;
    }
}
