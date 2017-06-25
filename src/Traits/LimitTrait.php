<?php
namespace Spindle\Collection\Traits;

trait LimitTrait
{
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
            $this->ops[] = '    if (!$' . $fn_name . '($_)) continue;';
        } else {
            $this->ops[] = '    if (!(' . $fn . ')) continue;';
        }
        return $this->step();
    }

    /**
     * @param string|callable $fn '$_ > 100'
     * @return $this
     */
    public function reject($fn)
    {
        $this->is_array = false;
        if (is_callable($fn)) {
            $fn_name = '_fn' . $this->fn_cnt++;
            $this->vars[$fn_name] = $fn;
            $this->ops[] = '    if ($' . $fn_name . '($_)) continue;';
        } else {
            $this->ops[] = '    if (' . $fn . ') continue;';
        }
        return $this->step();
    }

    /**
     * @param int $offset
     * @param ?int $length
     * @return $this
     */
    public function slice($offset, $length = null)
    {
        if ($offset < 0) {
            return new $this(array_slice($this->toArray(), $offset, $length), $this->debug);
        }
        $this->ops[] = '    if ($_i < ' . $offset . ') continue;';
        if ($length !== null) {
            $this->ops[] = '    if ($_i >= ' . ($offset + $length) . ') break;';
        }
        return $this->step();
    }
}
