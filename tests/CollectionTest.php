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
        self::assertEquals(['a','b','c'], Collection::range('a', 'c')->toArray());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRangeValidation()
    {
        Collection::range(1, 10, 1.1);
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

    public function testGetIterator()
    {
        $_ = Collection::range(1, 5);

        $arr = [];
        foreach ($_ as $key => $val) {
            $arr[$key] = $val;
        }

        self::assertEquals(range(1, 5), $arr);

        $_ = Collection::from(range(1, 5));

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

    /**
     * @expectedException \ErrorException
     */
    public function testDebugMode()
    {
        Collection::range(1, 5, 1, 'debug')
            ->map('$_')
            ->map('uso800')
            ;
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDebugModeAtPhp7()
    {
        if (PHP_VERSION_ID < 70000) {
            return $this->markTestSkipped();
        }
        Collection::range(1, 5, 1, 'debug')
            ->map('a b c')
            ;
    }
}
