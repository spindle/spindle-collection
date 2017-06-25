<?php
namespace Spindle\Collection\Traits;

use Spindle\Collection\Collection;

class TransformTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testMap()
    {
        $_ = Collection::range(1, 10);
        self::assertEquals(110, $_->map('$_ * 2')->sum());
        $_ = Collection::range(1, 10);
        self::assertEquals(110, $_->map(function ($_) { return $_ * 2; })->sum());
    }

    public function testMapWithKey()
    {
        $_ = Collection::range(1, 3);
        self::assertEquals(0*1 + 1*2 + 2*3, $_->mapWithKey('$_ * $_key')->sum());
        $_ = Collection::range(1, 3);
        self::assertEquals(0*1 + 1*2 + 2*3, $_->mapWithKey(function ($key, $val) { return $key * $val; })->sum());
    }

    public function testColumn()
    {
        $_ = Collection::from(array_fill(0, 4, [1,2,3]));
        self::assertEquals([[1],[1],[1],[1]], $_->column([0])->toArray());
    }

    public function testFlip()
    {
        $_ = Collection::from(['a', 'b', 'c']);
        self::assertEquals(['a'=>0, 'b'=>1, 'c'=>2], $_->flip()->toArray());
    }
}
