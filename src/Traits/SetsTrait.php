<?php
namespace Spindle\Collection\Traits;

trait SetsTrait
{
    /**
     * @param int $size
     * @return \Spindle\Collection\Collection
     */
    public function chunk($size)
    {
        return new $this(array_chunk($this->toArray(), $size), $this->debug);
    }

    /**
     * @return \Spindle\Collection\Collection
     */
    public function unique()
    {
        return new $this(array_unique($this->toArray()), $this->debug);
    }

    /**
     * @return \Spindle\Collection\Collection
     */
    public function groupBy($fn)
    {
        $array = $this->toArray();
        $mapped = (new $this($array))->map($fn)->toArray();
        $grouped = [];
        foreach ($mapped as $key => $val) {
            $grouped[$val][$key] = $array[$key];
        }

        return new $this($grouped, $this->debug);
    }

    /**
     * SQL's LEFT JOIN
     * - in order to avoid N+1 problem
     */
    public function leftJoin($map, callable $fetch, callable $combine)
    {
        $array = $this->toArray();

        if ($map) {
            $keys = (new $this($array))->map($map)->toArray();
            $mapped = [];
            foreach ($keys as $key => $val) {
                $mapped[$val][] = $key;
            }
        } else {
            $mapped = [];
            foreach ($array as $key => $val) {
                $mapped[$key] = [$key];
            }
        }
        $keys = array_keys($mapped);
        $fetched = $fetch($keys);
        if (!is_array($fetched)) {
            throw new \UnexpectedValueException(
                'leftJoin($map, $fetch, $combine) $fetch must be function-type [key1, key2, ...] => [key1 => val1, ...]'
            );
        }

        foreach ($fetched as $key => $val) {
            if (!isset($mapped[$key])) {
                throw new \UnexpectedValueException(
                    'leftJoin($map, $fetch, $combine) $fetch returned an array having unexpected key: ' . $key
                );
            }
            foreach ($mapped[$key] as $originKey) {
                $array[$originKey] = $combine($array[$originKey], $val);
            }
        }

        return new $this($array, $this->debug);
    }
}
