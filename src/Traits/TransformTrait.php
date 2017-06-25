<?php
namespace Spindle\Collection\Traits;

trait TransformTrait
{
    /**
     * @param string|callable $fn ($val) => $val
     * @return \Spindle\Collection\Collection
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
     * @param string|callable $fn ($key, $val) => $val
     * @return \Spindle\Collection\Collection
     */
    public function mapWithKey($fn)
    {
        $this->is_array = false;
        if (is_callable($fn)) {
            $fn_name = '_fn' . $this->fn_cnt++;
            $this->vars[$fn_name] = $fn;
            $this->ops[] = '    $_ = $' . $fn_name . '($_key, $_);';
        } else {
            $this->ops[] = '    $_ = ' . $fn . ';';
        }
        return $this->step();
    }

    /**
     * @param string|string[] columns
     * @return \Spindle\Collection\Collection
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
            $exported = var_export($columns, 1);
            $this->ops[] = "    \$_ = \$_[$columns];";
        }
        return $this->step();
    }

    /**
     * @return \Spindle\Collection\Collection
     */
    public function flip()
    {
        $this->is_array = false;
        $this->ops[] = '    list($_key, $_) = [$_, $_key];';
        return $this->step();
    }
}
