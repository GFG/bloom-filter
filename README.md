# PHP Bloom Filter

A Bloom filter is a space-efficient probabilistic data structure, conceived by Burton Howard Bloom in 1970,
that is used to test whether an element is a member of a set (False positive matches are possible, but false negatives are not).


```php
<?php

use \RocketLabs\BloomFilter\Persist\Redis;
use \RocketLabs\BloomFilter\BloomFilter;
use \RocketLabs\BloomFilter\Hash\Murmur;
use \RocketLabs\BloomFilter\Persist\BitString;

$setToStore = [
    'Test string 1',
    'Test string 2',
    'Test string 3',
    'Test string 4',
    'Test string 5',
];

$redisParams = [
    'host' => 'localhost',
    'port' => 6379,
    'db' => 0,
    'key' => 'bloom_filter',
];
$persisterRedis = Redis::create($redisParams);
$persisterInRam = new BitString();

# Create via factory method
$filter = BloomFilter::createFromSetSize(
    $persisterRedis,
    new Murmur(),
    count($setToStore)
);

# Create via constructor and init after that
$filter = new BloomFilter(Redis::create(), new Murmur());
$filter->init(count($setToStore));

foreach ($setToStore as $string) {
    $filter->add($string);
}

if ($filter->has('Test string 1')) {
    echo 'Possibly in set"' . PHP_EOL;
} else {
    echo 'Definitely not in set' . PHP_EOL;
}
```
