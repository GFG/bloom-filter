<?php
require_once __DIR__ . '/../vendor/autoload.php';

use RocketLabs\BloomFilter\DynamicBloomFilter;
use RocketLabs\BloomFilter\Hash\Murmur;
use RocketLabs\BloomFilter\Persist\BitString;

$handle = fopen(__DIR__ . "/data.csv", "r");

while (($data = fgetcsv($handle) ) !== false) {
    $messages[] = $data[0];
}

$bitString = new BitString();
$bitStringDynamicBloomFilter = new DynamicBloomFilter($bitString, new Murmur());
$bitStringDynamicBloomFilter->setSize(200);

//Adding messages to the bloom filter;
$bitStringDynamicBloomFilter->addBulk($messages);

//Checking
foreach ($messages as $message) {
    if (!$bitStringDynamicBloomFilter->has($message)) {
        echo $message . ' not in set' . PHP_EOL;
    }
}
//Checking not existing message
if (!$bitStringDynamicBloomFilter->has('new message')) {
    echo '"new message" is definitely not in set' . PHP_EOL;
}

//Suspending
$mementoRedisPersister = $bitStringDynamicBloomFilter->saveState();
$storedBitString = (string) $bitString;

//Restoring
$restoredDynamicBloomFilter = new DynamicBloomFilter(BitString::createFromString($storedBitString), new Murmur());
$restoredDynamicBloomFilter->restoreState($mementoRedisPersister);

//Checking
foreach ($messages as $message) {
    if (!$restoredDynamicBloomFilter->has($message)) {
        echo $message . ' not in set' . PHP_EOL;
    }
}
//Checking not existing message
if (!$restoredDynamicBloomFilter->has('new message')) {
    echo 'Restored object: "new message" is definitely not in set' . PHP_EOL;
}
