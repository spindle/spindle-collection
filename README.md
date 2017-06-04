spindle/collection
===================

The fastest collection library

- construct code and run with `eval()`
- very quick & low memory usage
- You must not pass user's input to this library. It might causes code injection vulnerability.

## Usage

```bash
composer require 'spindle/collection'
```

```php
<?php
require_once 'vendor/autoload.php';

use Spindle\Collection as _;

$collection = _::range(1, 100);
// or $collection = _::from($some_iterable); 
$collection
    ->filter('$_ % 2')
    ->map('$_ * 2')
    ->assignTo($val);
```

## Methods

### map($fn)

```php
_::range(1,4)->map('$_ * 2')->dump();
// 2,4,6,8
```

### filter($fn)

- `$fn($_) == true` ==> remain
- `$fn($_) == false` ==> remove

```php
_::range(1,4)->filter('$_ % 2')->dump();
// 1,3
```

### unique()

```php
_::from([1,1,2,1])->unique()->dump();
// 1
```

### column(array $columns)

Inspired by `array_column`.

```php
$a = ['a' => 1, 'b' => 2, 'c' => 3];

_::from([$a, $a])->column(['a', 'c'])->dump();
// ['a' => 1, 'c' => 3], ['a' => 1, 'c' => 3];
```

### slice($offset, $length = null)

### chunk($size)

### reduce($fn, $initial = null)

**not chainable**

```php
$max = _::from([1,2,3,4])->reduce('$_ > $_carry ? $_ : $_carry');
```

### sum()

### product()

### flip()

exchange value and key.

```php
_::from(['a' => 1, 'b' => 2, 'c' => 3])->flip()->dump();
// [1 => 'a', 2 => 'b', 3 => 'c'];
```

### sort($sort_flags = \SORT_REGULAR)

### rsort($sort_flags = \SORT_REGULAR)

### usort(callable $cmp)

### assignTo(&$val)

export to variable

```php
$val = _::from([1,2,3])->map('$_ * 2');
// equals
_::from([1,2,3])->map('$_ * 2')->assignTo($val);
```

### dump()

```php
$_->dump();
// equals
var_dump($_->toArray());
```
