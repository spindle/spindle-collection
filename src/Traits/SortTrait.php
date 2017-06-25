<?php
namespace Spindle\Collection\Traits;

trait SortTrait
{
    /**
     * @return \Spindle\Collection\Collection (new instance)
     */
    public function usort(callable $cmp)
    {
        $array = $this->toArray();
        usort($array, $cmp);
        return new $this($array, $this->debug);
    }

    /**
     * @param int $sort_flags \SORT_REGULAR|\SORT_NUMERIC|\SORT_STRING
     * @return \Spindle\Collection\Collection (new instance)
     */
    public function rsort($sort_flags = \SORT_REGULAR)
    {
        $array = $this->toArray();
        rsort($array, $sort_flags);
        return new $this($array, $this->debug);
    }

    /**
     * @param int $sort_flags \SORT_REGULAR|\SORT_NUMERIC|\SORT_STRING
     * @return \Spindle\Collection\Collection (new instance)
     */
    public function sort($sort_flags = \SORT_REGULAR)
    {
        $array = $this->toArray();
        sort($array, $sort_flags);
        return new $this($array, $this->debug);
    }

    /**
     * stable sort
     * @return \Spindle\Collection\Collection (new instance)
     */
    public function usortStable(callable $cmp)
    {
        $array = $this->map('[$_, $_i]')->toArray();
        usort($array, static function ($a, $b) use ($cmp) {
            return $cmp($a[0], $b[0]) ?: ($a[1] - $b[1]);
        });
        $sorted = new $this($array, $this->debug);
        $sorted->column(0)->toArray();
        return $sorted;
    }

    /**
     * sort with Schwartzian Transform
     * @param string|callable $fn map function
     * @param SORT_ASC|SORT_DESC $sort_order
     * @param SORT_REGULAR|SORT_NUMERIC|SORT_STRING $sort_flags
     * @return \Spindle\Collection (new instance)
     */
    public function mapSort($fn, $sort_order = \SORT_ASC, $sort_flags = \SORT_REGULAR)
    {
        $array = $this->toArray();
        $mapped = (new static($array))->map($fn)->toArray();
        array_multisort($mapped, $sort_order, $sort_flags, $array);
        return new $this($array, $this->debug);
    }
}
