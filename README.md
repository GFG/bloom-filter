# PHP Bloom Filter

A Bloom filter is a space-efficient probabilistic data structure, conceived by Burton Howard Bloom in 1970,
that is used to test whether an element is a member of a set (False positive matches are possible, but false negatives are not).


```php
<?php

use \RocketLabs\BloomFilter\Persist\Redis;
use \RocketLabs\BloomFilter\BloomFilter;

$setToStore = [
    'Test string 1',
    'Test string 2',
    'Test string 3',
    'Test string 4',
    'Test string 5',
];

$filter = BloomFilter::createFromApproximateSize(
    Redis::create(),
    count($setToStore),
    0.001
);

foreach ($setToStore as $string) {
    $filter->add($string);
}

if ($filter->has('Test string 1')) {
    echo 'Possibly in set"' . PHP_EOL;
} else {
    echo 'Definitely not in set' . PHP_EOL;
}
```
