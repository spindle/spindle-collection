<?php
namespace Spindle;

class Collection implements \IteratorAggregate
{
    private $ops = [];
    private $seed;
    private $is_array;
    private $vars = [];
    private $fn_cnt = 0;

    /**
     * @return \Spindle\Collection
     */
    public static function from($iterable)
    {
        return new static($iterable);
    }

    /**
     * Generator-based range()
     * @param int|string $start
     * @param int|string $end
     * @param int $step
     * @return \Spindle\Collection
     */
    public static function range($start, $end, $step = 1)
    {
        return new static(self::xrange($start, $end, $step));
    }

    private static function xrange($start, $end, $step = 1)
    {
        $_i = $start;
        while ($_i <= $end) {
            yield $_i;
            for ($j = $step; $j > 0; --$j) {
                ++$_i;
            }
        }
    }

    /**
     * @param iterable $seed
     */
    public function __construct($seed)
    {
        if (!is_array($seed) && !is_object($seed)) {
            throw new \InvalidArgumentException('$seed should be iterable, given ' . gettype($seed));
        }
        $this->is_array = is_array($seed);
        $this->seed = $seed;
    }

    /**
     * @param string|callable $fn '$_ > 100'
     * @return \Spindle\Collection (new instance)
     */
    public function filter($fn)
    {
        $new = clone $this;
        $new->is_array = false;
        if (is_callable($fn)) {
            $fn_name = '_fn' . $new->fn_cnt++;
            $new->vars[$fn_name] = $fn;
            $new->ops[] = 'if (!$' . $fn_name . '($_)) continue;';
        } else {
            $new->ops[] = 'if (!(' . $fn . ')) continue;';
        }
        return $new;
    }

    /**
     * @param string|callable $fn '$_ * 2'
     * @return \Spindle\Collection (new instance)
     */
    public function map($fn)
    {
        $new = clone $this;
        $new->is_array = false;
        if (is_callable($fn)) {
            $fn_name = '_fn' . $new->fn_cnt++;
            $new->vars[$fn_name] = $fn;
            $new->ops[] = '$_ = $' . $fn_name . '($_);';
        } else {
            $new->ops[] = '$_ = ' . $fn . ';';
        }
        return $new;
    }

    /**
     * @param string[] column
     * @return \Spindle\Collection (new instance)
     */
    public function column(array $columns)
    {
        $new = clone $this;
        $new->is_array = false;
        $defs = [];
        foreach ($columns as $key) {
            $exported = var_export($key, 1);
            $defs[] = "$exported => \$_[$exported]";
        }
        $new->ops[] = '$_ = [' . implode(',', $defs) . '];';
        return $new;
    }

    /**
     * @param int $offset
     * @param ?int $length
     * @return \Spindle\Collection (new instance)
     */
    public function slice($offset, $length = null)
    {
        if ($offset < 0) {
            return new $this(array_slice($this->toArray(), $offset, $length));
        }
        $new = clone $this;
        $new->ops[] = 'if ($_i < ' . $offset . ') continue;';
        $new->ops[] = 'if ($_i > ' . $offset + $length . ') break;';
        return $new;
    }

    /**
     * @param int $size
     * @return \Spindle\Collection (new instance)
     */
    public function chunk($size)
    {
        return new $this(array_chunk($this->toArray(), $size));
    }

    /**
     * @return \Spindle\Collection (new instance)
     */
    public function unique()
    {
        return new $this(array_unique($this->toArray()));
    }

    /**
     * @return \Spindle\Collection (new instance)
     */
    public function flip()
    {
        $new = clone $this;
        $new->is_array = false;
        $new->ops[] = 'list($_key, $_) = array($_, $_key);';
        return $new;
    }

    /**
     * @param string|callable $fn '$_carry + $_'
     * @param mixed $initial
     * @return \Spindle\Collection (new instance)
     */
    public function reduce($fn, $initial = null)
    {
        $ops = $this->ops;
        $this->vars['_carry'] = $initial;
        $ops[] = '$_carry = ' . $fn . ';';
        $after = '$_result = $_carry;';
        return self::evaluate($this->seed, $this->vars, $this->compile($ops), '', $after);
    }

    /**
     * @return int|float
     */
    public function sum()
    {
        $ops = $this->ops;
        $before = '$_result = 0;';
        $ops[] = '$_result += $_;';

        return self::evaluate($this->seed, $this->vars, $this->compile($ops), $before, '');
    }

    /**
     * @return int|float
     */
    public function product()
    {
        $ops = $this->ops;
        $before = '$_result = 1;';
        $ops[] = '$_result *= $_;';

        return self::evaluate($this->seed, $this->vars, $this->compile($ops), $before, '');
    }

    /**
     * @return \Spindle\Collection (new instance)
     */
    public function usort(callable $cmp)
    {
        $array = $this->toArray();
        usort($array, $cmp);
        return new $this($array);
    }

    /**
     * @return \Spindle\Collection (new instance)
     */
    public function rsort($sort_flags = \SORT_REGULAR)
    {
        $array = $this->toArray();
        rsort($array, $sort_flags);
        return new $this($array);
    }

    /**
     * @return \Spindle\Collection (new instance)
     */
    public function sort($sort_flags = \SORT_REGULAR)
    {
        $array = $this->toArray();
        sort($array, $sort_flags);
        return new $this($array);
    }

    /**
     * @return \Generator
     */
    public function getIterator()
    {
        $ops = $this->ops;
        $ops[] = 'yield $_key => $_;';
        $gen = self::evaluate(
            $this->_seed,
            $this->vars,
            $this->compile($ops),
            '$_result = static function() use($_seed){',
            '};'
        );
        return $gen();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if ($this->is_array) {
            return $this->seed;
        }
        $ops = $this->ops;
        $ops[] = '$_result[$_key] = $_;';
        return self::evaluate(
            $this->seed,
            $this->vars,
            $this->compile($ops),
            '$_result = [];',
            ''
        );
    }

    /**
     * @return $this
     */
    public function dump()
    {
        var_dump($this->toArray());
        return $this;
    }

    /**
     * @return $this
     */
    public function assignTo(&$var)
    {
        $var = $this;
        return $this;
    }

    /**
     * @return $this
     */
    public function assignArrayTo(&$var)
    {
        $var = $this->toArray();
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->compile($this->ops);
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        if (is_array($this->seed)) {
            $cnt = count($this->seed);
            if ($cnt === 0) {
                $seed = "empty array()";
            } else {
                $first = gettype(current($this->seed));
                $seed = "array($first, ...($cnt items))";
            }
        } else {
            $seed = get_class($this->seed);
        }
        return [
            'seed' => $seed,
            'code' => $this->compile($this->ops),
        ];
    }

    private static function evaluate($_seed, $_vars, $_code, $_before, $_after)
    {
        $_result = null;
        extract($_vars);
        eval("$_before \n $_code \n $_after");
        return $_result;
    }

    private static function compile($ops)
    {
        return '$_i = 0; foreach ($_seed as $_key => $_) {'
            . '++$_i;'
            . implode("\n", $ops)
            . '}';
    }
}
