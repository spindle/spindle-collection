<?php
namespace Spindle;

class Collection implements \IteratorAggregate
{
    private $ops = [];
    private $seed;
    private $isArray;
    private $vars = [];
    private $fn_cnt = 0;

    public static function from($iterable)
    {
        return new static($iterable);
    }

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

    public function __construct($seed)
    {
        if (!is_array($seed) && !is_object($seed)) {
            throw new \InvalidArgumentException('$seed should be traversable');
        }
        $this->is_array = is_array($seed);
        $this->seed = $seed;
    }

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

    public function slice($offset, $length = null)
    {
        if ($offset < 0) {
            $this->seed = array_slice($this->toArray(), $offset, $length);
            $this->clear();
            return $this;
        }
        $this->ops[] = 'if ($_i < ' . $offset . ') continue;';
        $this->ops[] = 'if ($_i > ' . $offset + $length . ') break;';
        return $this;
    }

    public function chunk($size)
    {
        $this->seed = array_chunk($this->toArray(), $size);
        $this->clear();
        return $this;
    }

    public function unique()
    {
        $this->seed = array_unique($this->toArray());
        $this->clear();
        return $this;
    }

    public function reduce($fn, $initial = null)
    {
        $ops = $this->ops;
        $this->vars['_carry'] = $initial;
        $ops[] = '$_carry = ' . $fn . ';';
        $after = '$_result = $_carry;';
        return self::evaluate($this->seed, $this->vars, $this->compile($ops), '', $after);
    }

    public function flip()
    {
        $this->is_array = false;
        $this->ops[] = 'list($_key, $_) = array($_, $_key);';
        return $this;
    }

    public function sum()
    {
        $ops = $this->ops;
        $before = '$_result = 0;';
        $ops[] = '$_result += $_;';

        return self::evaluate($this->seed, $this->vars, $this->compile($ops), $before, '');
    }

    public function product()
    {
        $ops = $this->ops;
        $before = '$_result = 1;';
        $ops[] = '$_result *= $_;';

        return self::evaluate($this->seed, $this->vars, $this->compile($ops), $before, '');
    }

    public function usort(callable $fn)
    {
        $array = $this->toArray();
        usort($array, $fn);
        $this->seed = $array;
        $this->clear();
        return $this;
    }

    public function rsort($sort_flags = \SORT_REGULAR)
    {
        $array = $this->toArray();
        rsort($array, $sort_flags);
        $this->seed = $array;
        $this->clear();
        return $this;
    }

    public function sort($sort_flags = \SORT_REGULAR)
    {
        $array = $this->toArray();
        sort($array, $sort_flags);
        $this->seed = $array;
        $this->clear();
        return $this;
    }

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

    public function __toString()
    {
        return $this->compile($this->ops);
    }

    private function clear()
    {
        $this->is_array = true;
        $this->ops = [];
        $this->vars = [];
        $this->fn_cnt = 0;
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
