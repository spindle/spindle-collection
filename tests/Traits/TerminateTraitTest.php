<?php
namespace Spindle\Collection\Traits;

use Spindle\Collection\Collection;

class TerminateTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testJoin()
    {
        $_ = Collection::range(1, 4);
        self::assertEquals('1:2:3:4', $_->join(':'));
    }

    public function testSum()
    {
        $_ = Collection::range(1, 10);
        self::assertEquals(55, $_->sum());
    }

    public function testProduct()
    {
        $_ = Collection::range(1, 4);
        self::assertEquals(24, $_->product());
    }

    public function testCount()
    {
        $_ = Collection::range(1, 4);
        self::assertEquals(4, $_->count());
    }

    public function testReduce()
    {
        $_ = Collection::range(1, 5);
        self::assertEquals(15, $_->reduce('$_carry + $_'));

        $_ = Collection::range(1, 5);
        self::assertEquals(15, $_->reduce(function($_, $_carry){ return $_carry + $_; }));
    }
}
