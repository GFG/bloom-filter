<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RocketLabs\BloomFilter\Persist\BitRedis;
use RocketLabs\BloomFilter\CountingBloomFilter;
use RocketLabs\BloomFilter\Hash\Murmur;
use RocketLabs\BloomFilter\Persist\CountRedis;

const REDIS_HOST = 'localhost';
const REDIS_PORT = 6379;
const REDIS_KEY = 'bloom_filter';
const REDIS_COUNT_KEY = 'counting_bloom_filter';
$redis = new \Redis();
$redis->connect(REDIS_HOST, REDIS_PORT);
$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
$redis->select(0);
$redis->del(REDIS_KEY);
$redis->del(REDIS_COUNT_KEY);
$handle = fopen(__DIR__ . "/data.csv", "r");

while (($data = fgetcsv($handle) ) !== false) {
    $messages[] = $data[0];
}

$redisCountingBloomFilter = new CountingBloomFilter(BitRedis::create(['key' => REDIS_KEY]), CountRedis::create(['key' => REDIS_COUNT_KEY]), new Murmur());
$redisCountingBloomFilter->setSize(1000);

//Adding messages to the bloom filter;
$redisCountingBloomFilter->addBulk($messages);

//Checking
foreach ($messages as $message) {
    if (!$redisCountingBloomFilter->has($message)) {
        echo $message . ' not in set' . PHP_EOL;
    }
}
//deleting
$redisCountingBloomFilter->deleteBulk($messages);

//Checking
foreach ($messages as $message) {
    if ($redisCountingBloomFilter->has($message)) {
        echo $message . ' is not in set but was deleted' . PHP_EOL;
    }
}


//deleting simple message
$redisCountingBloomFilter->add('new message for deleting');
$redisCountingBloomFilter->delete('new message for deleting');

if (!$redisCountingBloomFilter->has('new message for deleting')) {
    echo '"new message for deleting" was deleted' . PHP_EOL;
}

//Checking not existing message
if (!$redisCountingBloomFilter->has('new message')) {
    echo '"new message" is definitely not in set' . PHP_EOL;
}