<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RocketLabs\BloomFilter\Persist\Redis;
use RocketLabs\BloomFilter\BloomFilter;
use RocketLabs\BloomFilter\DynamicBloomFilter;
use RocketLabs\BloomFilter\Hash\Murmur;
use RocketLabs\BloomFilter\Persist\BitString;

const REDIS_HOST = 'localhost';
const REDIS_PORT = 6379;
const REDIS_KEY = 'dynamic_bloom_filter';
$redis = new \Redis();
$redis->connect(REDIS_HOST, REDIS_PORT);
$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
$redis->select(0);
$redis->del('dynamic_bloom_filter');
$handle = fopen(__DIR__ . "/data.csv", "r");

while (($data = fgetcsv($handle) ) !== false) {
    $messages[] = $data[0];
}

$redisDynamicBloomFilter = new DynamicBloomFilter(Redis::create(['key' => REDIS_KEY]), new Murmur());
$redisDynamicBloomFilter->setSize(200);

$bitString = new BitString();
$bitStringDynamicBloomFilter = new DynamicBloomFilter($bitString, new Murmur());
$bitStringDynamicBloomFilter->setSize(200);

//Adding messages to the bloom filter;
$redisDynamicBloomFilter->addBulk($messages);

//Checking
foreach ($messages as $message) {
    if (!$redisDynamicBloomFilter->has($message)) {
        echo $message . ' not in set' . PHP_EOL;
    }
}
//Checking not existing message
if (!$redisDynamicBloomFilter->has('new message')) {
    echo '"new message" is definitely not in set' . PHP_EOL;
}

//Suspending
$mementoRedisPersister = $redisDynamicBloomFilter->suspend();

//Restoring
$restoredDynamicBloomFilter = new DynamicBloomFilter(Redis::create(['key' => REDIS_KEY]), new Murmur());
$restoredDynamicBloomFilter->restore($mementoRedisPersister);

//Checking
foreach ($messages as $message) {
    if (!$restoredDynamicBloomFilter->has($message)) {
        echo $message . ' not in set' . PHP_EOL;
    }
}
//Checking not existing message
if (!$restoredDynamicBloomFilter->has('new message')) {
    echo '"Restored Object: new message" is definitely not in set' . PHP_EOL;
}