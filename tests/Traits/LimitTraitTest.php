<?php
namespace Spindle\Collection\Traits;

use Spindle\Collection\Collection;

class LimitTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testSlice()
    {
        $_ = Collection::range(1, 5);
        self::assertEquals([2,3,4], array_values($_->slice(2,3)->toArray()));

        $_ = Collection::range(1, 5);
        self::assertEquals([2,3,4,5], array_values($_->slice(2)->toArray()));

        $_ = Collection::range(1, 5);
        self::assertEquals([4,5], array_values($_->slice(-2)->toArray()));
    }

    public function testFilter()
    {
        $_ = Collection::range(1, 10);
        self::assertEquals(1 + 3 + 5 + 7 + 9, $_->filter('$_ % 2')->sum());
        $_ = Collection::range(1, 10);
        self::assertEquals(1 + 3 + 5 + 7 + 9, $_->filter(function ($_) { return $_ % 2; })->sum());
    }

    public function testReject()
    {
        $_ = Collection::range(1, 10);
        self::assertEquals(2 + 4 + 6 + 8 + 10, $_->reject('$_ % 2')->sum());
        $_ = Collection::range(1, 10);
        self::assertEquals(2 + 4 + 6 + 8 + 10, $_->reject(function ($_) { return $_ % 2; })->sum());
    }
}
