<?php
namespace Spindle\Collection;

class Collection implements \IteratorAggregate
{
    use Traits\SortTrait;
    use Traits\SetsTrait;
    use Traits\TerminateTrait;
    use Traits\LimitTrait;

    const TYPE_FOR = 'for';
    const TYPE_FOREACH = 'foreach';

    private $type = self::TYPE_FOREACH;
    private $debug = false;

    private $ops = [];
    private $seed;
    private $is_array;
    private $vars = [];
    private $fn_cnt = 0;

    /**
     * @return \Spindle\Collection
     */
    public static function from($iterable, $debug = null)
    {
        return new static($iterable, $debug);
    }

    /**
     * Generator-based range()
     * @param int|string $start
     * @param int|string $end
     * @param int $step
     * @param bool $debug
     * @return \Spindle\Collection
     */
    public static function range($start, $end, $step = 1, $debug = null)
    {
        if (!is_int($step) || $step < 1) {
            throw new \InvalidArgumentException('$step must be natural number: ' . $step);
        }
        if (is_numeric($start) && is_numeric($end)) {
            $seed = [
                '$_current = ' . $start,
                '$_current <= ' . $end,
                $step === 1 ? '++$_current' : '$_current += ' . $step,
            ];
        } else {
            $seed = [
                '$_current = ' . var_export($start, 1),
                '$_current <= ' . var_export($end, 1),
                implode(',', array_fill(0, $step, '++$_current')),
            ];
        }
        return new static($seed, $debug, self::TYPE_FOR);
    }

    /**
     * Generator-based repeat()
     * @param mixed $elem
     * @param int $count
     * @param bool $debug
     */
    public static function repeat($elem, $count, $debug = null)
    {
        if (!is_int($count) || $count < 0) {
            throw new \InvalidArgumentException('$count must be int >= 0. given: ' . gettype($count));
        }
        $seed = [
            '$_current = $_elem, $_count = ' . var_export($count, 1),
            '$_count > 0',
            '--$_count'
        ];
        $collection = new static($seed, $debug, self::TYPE_FOR);
        $collection->vars['_elem'] = $elem;
        return $collection;
    }

    /**
     * @param iterable $seed
     */
    public function __construct($seed, $debug = null, $type = null)
    {
        if (!is_array($seed) && !is_object($seed)) {
            throw new \InvalidArgumentException('$seed should be iterable, given ' . gettype($seed));
        }
        $this->seed = $seed;
        if ($debug) {
            $this->debug = true;
        }
        if ($type === self::TYPE_FOR) {
            $this->type = $type;
            $this->is_array = false;
            return;
        }
        $this->is_array = is_array($seed);
    }

    private function step()
    {
        if ($this->debug) {
            $this->toArray();
            return $this;
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
            $this->ops[] = '    $_ = $' . $fn_name . '($_);';
        } else {
            $this->ops[] = '    $_ = ' . $fn . ';';
        }
        return $this->step();
    }

    /**
     * @param string|string[] columns
     * @return $this
     */
    public function column($columns)
    {
        $this->is_array = false;
        if (is_array($columns)) {
            $defs = [];
            foreach ($columns as $key) {
                $exported = var_export($key, 1);
                $defs[] = "$exported => \$_[$exported]";
            }
            $this->ops[] = '    $_ = [' . implode(',', $defs) . '];';
        } else {
            $exported = var_export($key, 1);
            $this->ops[] = "    \$_ = \$_[$key];";
        }
        return $this->step();
    }

    /**
     * @return \Spindle\Collection (new instance)
     */
    public function flip()
    {
        $this->is_array = false;
        $this->ops[] = '    list($_key, $_) = [$_, $_key];';
        return $this->step();
    }

    /**
     * @return \Generator|\ArrayIterator
     */
    public function getIterator()
    {
        if ($this->is_array) {
            return new \ArrayIterator($this->seed);
        }
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
        $ops[] = '    $_result[$_key] = $_;';
        $array = self::evaluate(
            $this->seed,
            $this->vars,
            $this->compile($ops),
            '$_result = [];',
            ''
        );
        $this->type = self::TYPE_FOREACH;
        $this->is_array = true;
        $this->ops = [];
        $this->seed = $array;
        $this->vars = [];
        $this->fn_cnt = 0;
        return $array;
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
        return $this;
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
        $_old_handler = set_error_handler(function($severity, $message, $file, $line){
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, E_ALL ^ E_DEPRECATED ^ E_USER_DEPRECATED);
        try {
            $_result = null;
            extract($_vars);
            eval("$_before \n $_code \n $_after");
        } catch (\ParseError $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        } finally {
            set_error_handler($_old_handler);
        }
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
            'for (' . implode('; ', $seed). ') {',
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
