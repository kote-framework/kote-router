<?php

namespace tests;

use PHPUnit\Framework\TestCase;

use function Nerd\Framework\Routing\Helper\filterArgs;

class FunctionsTest extends TestCase
{
    public function testFilterArgs()
    {
        $this->assertEquals([1, 2, 3], filterArgs([1, 2, 3]));
        $this->assertEquals(["a" => 1, "b" => 2], filterArgs(["a" => 1, "b" => 2, 3, 4]));
    }
}
