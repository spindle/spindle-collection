<?php
namespace Spindle\Collection;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructCheck()
    {
        new Collection(123);
    }

    public function testRange()
    {
        self::assertEquals([1,2,3,4,5,6,7,8,9,10], Collection::range(1, 10)->toArray());
    }

    public function testRepeat()
    {
        self::assertEquals([1,1,1,1,1], Collection::repeat(1, 5)->toArray());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRepeatParam()
    {
        Collection::repeat(1, -5);
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

    public function testSlice()
    {
        $_ = Collection::range(1, 5);
        self::assertEquals([2,3,4], array_values($_->slice(2,3)->toArray()));

        $_ = Collection::range(1, 5);
        self::assertEquals([2,3,4,5], array_values($_->slice(2)->toArray()));

        $_ = Collection::range(1, 5);
        self::assertEquals([4,5], array_values($_->slice(-2)->toArray()));
    }

    public function testChunk()
    {
        $_ = Collection::range(1, 5);
        $expect = [
            [1, 2],
            [3, 4],
            [5],
        ];

        self::assertEquals($expect, $_->chunk(2)->toArray());
    }

    public function testGetIterator()
    {
        $_ = Collection::range(1, 5);

        $arr = [];
        foreach ($_ as $key => $val) {
            $arr[$key] = $val;
        }

        self::assertEquals(range(1, 5), $arr);
    }

    public function testDump()
    {
        $_ = Collection::from(Collection::range(1, 5));

        ob_start();
        $_->dump();
        $resp = ob_get_clean();
        self::assertContains('"Spindle\Collection\Collection"', $resp);

        $_ = new Collection([]);
        ob_start();
        $_->dump();
        $resp = ob_get_clean();
        self::assertContains('"empty array()"', $resp);

        $_ = Collection::from(range(1, 5));

        ob_start();
        $_->dump();
        $resp = ob_get_clean();
        self::assertContains('"array(integer, ...(5 items))"', $resp);
    }

    public function testToString()
    {
        $_ = Collection::range(1, 5);
        $_->map('$_ * 2')
          ->filter('$_ > 5');
        $expect = <<<_EXPECT_
Spindle\Collection\Collection
 array-mode:0
 codes:
      \$_ = \$_ * 2;
      if (!(\$_ > 5)) continue;
_EXPECT_;
        self::assertEquals($expect, (string)$_);
    }

    public function testReduce()
    {
        $_ = Collection::range(1, 5);
        self::assertEquals(15, $_->reduce('$_carry + $_'));

        $_ = Collection::range(1, 5);
        self::assertEquals(15, $_->reduce(function($_, $_carry){ return $_carry + $_; }));
    }

    public function testSort()
    {
        self::assertEquals([1,2,3,4,5], Collection::from([3, 2, 5, 1, 4])->sort()->toArray());
    }

    public function testRsort()
    {
        self::assertEquals([5,4,3,2,1], Collection::from([3, 2, 5, 1, 4])->rsort()->toArray());
    }

    public function testUsort()
    {
        self::assertEquals([1,2,3,4,5], Collection::from([3, 2, 5, 1, 4])->usort(function($a, $b){ return $a - $b; })->toArray());
    }

    public function testAssignTo()
    {
        $_ = Collection::range(1, 5)->map('$_ * 2')->assignTo($assigned);
        self::assertSame($_, $assigned);
    }

    public function testAssignArrayTo()
    {
        Collection::range(1, 5)->map('$_ * 2')->assignArrayTo($assigned);
        self::assertInternalType('array', $assigned);
        self::assertEquals([2,4,6,8,10], $assigned);
    }
}
