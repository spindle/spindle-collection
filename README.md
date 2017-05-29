spindle/collection
===================

fastest collection library

## Usage

```php
use Spindle\Collection as _;

$collection = _::range(1, 100);
// or $collection = _::from($some_iterable); 
$collection
    ->filter('$_ % 2')
    ->map('$_ * 2');

echo $collection->sum();
```
