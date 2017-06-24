<?php
namespace Spindle;

class Collection implements \IteratorAggregate
{
    const TYPE_FOR = 'for';
    const TYPE_FOREACH = 'foreach';

    private $type = self::TYPE_FOREACH;

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
        $seed = [
            '$_current = ' . var_export($start, 1),
            '$_current != ' . var_export(++$end, 1),
            implode(',', array_fill(0, $step, '++$_current')),
        ];
        return new static($seed, self::TYPE_FOR);
    }

    /**
     * Generator-based repeat()
     * @param mixed $elem
     * @param int $count
     */
    public static function repeat($elem, $count)
    {
        if (!is_int($count) || $count < 0) {
            throw new \InvalidArgumentException('$count must be int >= 0. given: ' . gettype($count));
        }
        $seed = [
            '$_current = $_elem, $_count = ' . var_export($count, 1),
            '$_count > 0',
            '--$_count'
        ];
        $collection = new static($seed, self::TYPE_FOR);
        $collection->vars['_elem'] = $elem;
        return $collection;
    }

    /**
     * @param iterable $seed
     */
    public function __construct($seed, $type = null)
    {
        if (!is_array($seed) && !is_object($seed)) {
            throw new \InvalidArgumentException('$seed should be iterable, given ' . gettype($seed));
        }
        $this->seed = $seed;
        if ($type === self::TYPE_FOR) {
            $this->type = $type;
            $this->is_array = false;
            return;
        }
        $this->is_array = is_array($seed);
    }

    /**
     * @param string|callable $fn '$_ > 100'
     * @return $this
     */
    public function filter($fn)
    {
        $this->is_array = false;
        if (is_callable($fn)) {
            $fn_name = '_fn' . $this->fn_cnt++;
            $this->vars[$fn_name] = $fn;
            $this->ops[] = 'if (!$' . $fn_name . '($_)) continue;';
        } else {
            $this->ops[] = 'if (!(' . $fn . ')) continue;';
        }
        return $this;
    }

    /**
     * @param string|callable $fn '$_ * 2'
     * @return $this
     */
    public function map($fn)
    {
        $this->is_array = false;
        if (is_callable($fn)) {
            $fn_name = '_fn' . $this->fn_cnt++;
            $this->vars[$fn_name] = $fn;
            $this->ops[] = '$_ = $' . $fn_name . '($_);';
        } else {
            $this->ops[] = '$_ = ' . $fn . ';';
        }
        return $this;
    }

    /**
     * @param string[] column
     * @return $this
     */
    public function column(array $columns)
    {
        $this->is_array = false;
        $defs = [];
        foreach ($columns as $key) {
            $exported = var_export($key, 1);
            $defs[] = "$exported => \$_[$exported]";
        }
        $this->ops[] = '$_ = [' . implode(',', $defs) . '];';
        return $this;
    }

    /**
     * @param int $offset
     * @param ?int $length
     * @return $this
     */
    public function slice($offset, $length = null)
    {
        if ($offset < 0) {
            return new $this(array_slice($this->toArray(), $offset, $length));
        }
        $this->ops[] = 'if ($_i < ' . $offset . ') continue;';
        if ($length !== null) {
            $this->ops[] = 'if ($_i >= ' . ($offset + $length) . ') break;';
        }
        return $this;
    }

    /**
     * @param int $size
     * @return $this
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
        $this->is_array = false;
        $this->ops[] = 'list($_key, $_) = array($_, $_key);';
        return $this;
    }

    /**
     * @param string|callable $fn '$_carry + $_'
     * @param mixed $initial
     * @return mixed
     */
    public function reduce($fn, $initial = null)
    {
        $ops = $this->ops;
        $this->vars['_carry'] = $initial;
        if (is_callable($fn)) {
            $fn_name = '_fn' . $this->fn_cnt++;
            $this->vars[$fn_name] = $fn;
            $ops[] = '$_carry = $' . $fn_name . '($_, $_carry);';
        } else {
            $ops[] = '$_carry = ' . $fn . ';';
        }
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
            $this->seed,
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
        var_dump($this);
        return $this;
    }

    /**
     * @return $this
     */
    public function assignTo(&$var = null)
    {
        $var = new $this($this->toArray());
        return $var;
    }

    /**
     * @return $this
     */
    public function assignArrayTo(&$var = null)
    {
        $var = $this->toArray();
        return new $this($var);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return implode("\n", [
            static::class,
            ' array-mode:' . (int)$this->is_array,
            " codes:\n  " . implode("\n  ", $this->ops)
        ]);
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

    private function compile($ops)
    {
        if ($this->type === self::TYPE_FOR) {
            return $this->compileFor($ops, $this->seed);
        }

        return $this->compileForeach($ops);
    }

    private static function compileFor($ops, $seed)
    {
        array_unshift(
            $ops,
            '$_i = 0;',
            'for (' . implode(';', $seed). ') {',
            '    $_key = $_i;',
            '    $_ = $_current;',
            '    ++$_i;'
        );

        $ops[] = '}';

        return implode("\n", $ops);
    }

    private static function compileForeach($ops)
    {
        array_unshift(
            $ops,
            '$_i = 0;',
            'foreach ($_seed as $_key => $_) {',
            '    ++$_i;'
        );
        $ops[] = '}';

        return implode("\n", $ops);
    }
}
