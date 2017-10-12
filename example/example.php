<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RocketLabs\BloomFilter\Persist\Redis;
use RocketLabs\BloomFilter\BloomFilter;
use RocketLabs\BloomFilter\DynamicBloomFilter;
use RocketLabs\BloomFilter\Hash\Murmur;
use RocketLabs\BloomFilter\Persist\BitString;

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

const REDIS_HOST = 'localhost';
const REDIS_PORT = 6379;

$redis = new \Redis();
$redis->connect(REDIS_HOST, REDIS_PORT);
$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
$redis->select(0);

$handle = fopen(__DIR__ . "/data.csv", "r");

while (($data = fgetcsv($handle) ) !== false) {
    $messages[] = $data[0];
}
$time_start = microtime_float();

$filters = [
    'Redis-BloomFilter' => (new BloomFilter(Redis::create(), new Murmur()))->setSize(1000),
    'Redis-DynamicBloomFilter' => (new DynamicBloomFilter(Redis::create(), new Murmur()))->setSize(200),
    'BitString-BloomFilter' => (new BloomFilter(new BitString(), new Murmur()))->setSize(1000),
    'BitString-DynamicBloomFilter' => (new DynamicBloomFilter(new BitString, new Murmur()))->setSize(200),
];

$mementos = [];

echo 'Messages: ' . count($messages) . "\n";

/**
 * @var  string $storage
 * @var \RocketLabs\BloomFilter\BloomFilterInterface $filter
 */
foreach ($filters as $storage => $filter) {
    $redis->del('bloom_filter');
    //Adding to filter
    $time_start = microtime_float();
    $filter->addBulk($messages);

    $time_end = microtime_float();
    $time = $time_end - $time_start;
    echo 'Adding. ' . $storage . " filter : $time sec\n";

    // checking
    $time_start = microtime_float();
    foreach ($messages as $message) {
        if (!$filter->has($message)) {
            echo $message . ' not in set' . PHP_EOL;
        }
    }
    // check not existing message
    if (!$filter->has('new message')) {
        echo '"new message" is definitely not in set' . PHP_EOL;
    }

    $time_end = microtime_float();
    $time = $time_end - $time_start;
    echo 'Checking. ' . $storage . " filter : $time sec\n\n";
}
