<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RocketLabs\BloomFilter\Persist\Redis;
use RocketLabs\BloomFilter\BloomFilter;
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
$redis->del('counting_bloom_filter');

$handle = fopen(__DIR__ . "/data.csv", "r");

while (($data = fgetcsv($handle) ) !== false) {
    $messages[] = $data[0];
}
$time_start = microtime_float();

$filters = [
    'Redis' => BloomFilter::createFromApproximateSize(Redis::create(), 10000, 0.001),
    'BitString' => BloomFilter::createFromApproximateSize(new BitString(), 10000, 0.001),
    'BitString Jenkins' => BloomFilter::createFromApproximateSize(new BitString(), 10000, 0.001),
    'BitString Jenkins 3 hash function' => BloomFilter::create(new BitString(), 100000, 3, ['Jenkins']),
    'BitString Crc32b' => BloomFilter::createFromApproximateSize(new BitString(), 10000, 0.001, ['Crc32b']),
];

echo 'Messages: ' . count($messages) . "\n";

foreach ($filters as $storage => $filter) {
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
