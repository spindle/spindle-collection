<?php
namespace Spindle;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testRange()
    {
        self::assertEquals([1,2,3,4,5,6,7,8,9,10], Collection::range(1, 10)->toArray());
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

    public function testMap()
    {
        $_ = Collection::range(1, 10);
        self::assertEquals(110, $_->map('$_ * 2')->sum());
        $_ = Collection::range(1, 10);
        self::assertEquals(110, $_->map(function ($_) { return $_ * 2; })->sum());
    }

    public function testFilter()
    {
        $_ = Collection::range(1, 10);
        self::assertEquals(1 + 3 + 5 + 7 + 9, $_->filter('$_ % 2')->sum());

        $_ = Collection::range(1, 10);
        self::assertEquals(1 + 3 + 5 + 7 + 9, $_->filter(function ($_) { return $_ % 2; })->sum());
    }

    public function testUnique()
    {
        $_ = Collection::from([1,1,1,1,1]);
        self::assertEquals([1], $_->unique()->toArray());
    }
}
