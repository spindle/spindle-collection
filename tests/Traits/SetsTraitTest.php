<?php
namespace Spindle\Collection\Traits;

use Spindle\Collection\Collection;

class SetsTraitTest extends \PHPUnit\Framework\TestCase
{
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

    public function testUnique()
    {
        $_ = Collection::from([1,1,1,1,1]);
        self::assertEquals([1], $_->unique()->toArray());
    }

    public function testGroupBy()
    {
        $_ = Collection::range(1, 10);
        $expect = [
            1 => [0=>1, 3=>4, 6=>7, 9=>10],
            2 => [1=>2, 4=>5, 7=>8],
            0 => [2=>3, 5=>6, 8=>9],
        ];
        self::assertEquals($expect, $_->groupBy('$_ % 3')->toArray());
    }

    public function testLeftJoin()
    {
        Collection::range(1, 5)
            ->leftJoin(
                null,
                function (array $keys) {
                    $arr = [];
                    foreach ($keys as $key) {
                        $arr[$key] = $key * 2;
                    }
                    return $arr;
                },
                function ($left, $right) {
                    return [$left, $right];
                }
            )
            ->assignArrayTo($result);

        $expect = [
            [1, 0],
            [2, 2],
            [3, 4],
            [4, 6],
            [5, 8],
        ];
        self::assertEquals($expect, $result);

        Collection::range(1, 5)
            ->leftJoin(
                '$_ % 2',
                function (array $keys) {
                    $this->assertCount(2, $keys);
                    $arr = [];
                    foreach ($keys as $key) {
                        $arr[$key] = $key * 2;
                    }
                    return $arr;
                },
                function ($left, $right) {
                    return [$left, $right];
                }
            )
            ->assignArrayTo($result);

        $expect = [
            [1, 2],
            [2, 0],
            [3, 2],
            [4, 0],
            [5, 2],
        ];
        self::assertEquals($expect, $result);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testLeftJoinInvalid1()
    {
        Collection::range(1, 5)
            ->leftJoin(
                '$_ % 2',
                function (array $keys) {
                    return 1;
                },
                function ($left, $right) {
                    return [$left, $right];
                }
            )
            ->assignArrayTo($result);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testLeftJoinInvalid2()
    {
        Collection::range(1, 5)
            ->leftJoin(
                '$_ % 2',
                function (array $keys) {
                    return ['a' => 1];
                },
                function ($left, $right) {
                    return [$left, $right];
                }
            )
            ->assignArrayTo($result);
    }
}
