<?php
namespace Spindle\Collection\Traits;

use Spindle\Collection\Collection;

class Person
{
    public $name, $age;

    function __construct($name, $age)
    {
        $this->name = $name;
        $this->age = $age;
    }

    function __toString()
    {
        return implode(', ', [
            $this->name,
            $this->age
        ]);
    }
}

class SortTraitTest extends \PHPUnit\Framework\TestCase
{
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

    public function testUsortStable()
    {
        $people = [
            new Person('Tanaka', 200),
            new Person('Tanaka', 199),
            new Person('Tanaka', 198),
            new Person('Tanaka', 197),
            new Person('Tanaka', 196),
            new Person('Tanaka', 195),
            new Person('Tanaka', 194),
            new Person('Tanaka', 193), 
            new Person('Tanaka', 192), 
            new Person('Tanaka', 191), 
            new Person('Tanaka', 190), 
            new Person('Tanaka', 189), 
            new Person('Tanaka', 188), 
            new Person('Tanaka', 187),  
            new Person('Tanaka', 186),   
            new Person('Tanaka', 185),
            new Person('Tanaka', 184), 
        ];

        Collection::from($people)
            ->usortStable(function ($a, $b) {
                return strcmp($a->name, $b->name);
            })
            ->assignArrayTo($result);

        self::assertEquals($people, $result, 'When each elem is equal, usortStable must not change order.');
    }

    public function testMapSort()
    {
        $people = [
            new Person('aaa', 200),
            new Person('bbb', 199),
            new Person('ccc', 198),
        ];

        Collection::from($people)
            ->mapSort('[$_->age, $_->name]')
            ->assignArrayTo($result);

        $expect = [
            new Person('ccc', 198),
            new Person('bbb', 199),
            new Person('aaa', 200),
        ];

        self::assertEquals($expect, $result);
    }
}
