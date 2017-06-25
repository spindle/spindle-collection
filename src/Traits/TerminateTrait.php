<?php
namespace Spindle\Collection\Traits;

trait TerminateTrait
{
    /**
     * @return string
     */
    public function join($separator)
    {
        $arr = $this->toArray();
        return implode($separator, $arr);
    }

    /**
     * @return int|float
     */
    public function sum()
    {
        return $this->reduce('$_carry + $_', 0);
    }

    /**
     * @return int|float
     */
    public function product()
    {
        return $this->reduce('$_carry * $_', 1);
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->reduce('$_carry + 1', 0);
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
            $ops[] = '    $_carry = $' . $fn_name . '($_, $_carry);';
        } else {
            $ops[] = '    $_carry = ' . $fn . ';';
        }
        $after = '$_result = $_carry;';
        return self::evaluate($this->seed, $this->vars, $this->compile($ops), '', $after);
    }

}
