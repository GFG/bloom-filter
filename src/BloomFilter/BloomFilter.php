<?php

namespace RocketLabs\BloomFilter;

use RocketLabs\BloomFilter\Hash\Hash;
use RocketLabs\BloomFilter\Persist\Persister;

/**
 * @author Igor Veremchuk igor.veremchuk@gmail.com
 */
class BloomFilter
{
    /** @var int */
    private $size;
    /** @var Persister */
    private $persister;
    /** @var Hash[]  */
    private $hashes;
    /** @var array */
    private $availableHashes = ['Crc32b', 'Fnv', 'Jenkins', 'Murmur'];

    /**
     * @param Persister $persister
     * @param int $approximateSize
     * @param float $falsePositiveProbability
     * @param array $hashFunctions
     * @return BloomFilter
     */
    public static function createFromApproximateSize(
        Persister $persister,
        $approximateSize,
        $falsePositiveProbability = 0.001,
        array $hashFunctions = []
    ) {
        $bitSize = self::optimalBitSize((int) $approximateSize, $falsePositiveProbability);
        $hashCount = self::optimalHashCount((int) $approximateSize, (int)$bitSize);

        return new self($persister, $bitSize, $hashCount, $hashFunctions);
    }

    /**
     * @param Persister $persister
     * @param int $bitSize
     * @param int $hashCount
     * @param array $hashFunctions
     * @return BloomFilter
     */
    public static function create($persister, $bitSize, $hashCount, array $hashFunctions = [])
    {
        return new self($persister, $bitSize, $hashCount, $hashFunctions);
    }

    /**
     * @param Persister $persister
     * @param int $size
     * @param int $hashCount
     * @param array $hashFunctions
     */
    public function __construct(Persister $persister, $size, $hashCount, array $hashFunctions = [])
    {
        $hashFunctions = !empty($hashFunctions) ? $hashFunctions : $this->availableHashes;

        if (!array_intersect($this->availableHashes, $hashFunctions)) {
            throw new \LogicException(
                sprintf('One or more of functions (%s) are not available', join(',', $hashFunctions))
            );
        }

        $this->persister = $persister;
        $this->size = $size;
        for ($i = 0; $i < $hashCount; $i++) {
            $hash = $hashFunctions[$i % count($hashFunctions)];
            $className = 'RocketLabs\\BloomFilter\\Hash\\' . $hash;
            $this->hashes[] = new $className;
        }
    }

    /**
     * @param string $value
     */
    public function add($value)
    {
        $this->persister->setBulk($this->getBits($value));
    }

    /**
     * @param array $valueList
     */
    public function addBulk(array $valueList)
    {
        $bits = [];
        foreach ($valueList as $value) {
            $bits[] = $this->getBits($value);
        }

        $this->persister->setBulk(call_user_func_array('array_merge', $bits));
    }

    /**
     * @param string $value
     * @return bool
     */
    public function has($value)
    {
        $bits = $this->persister->getBulk($this->getBits($value));

        return !in_array(0, $bits);
    }

    /**
     * m = ceil((n * log(p)) / log(1.0 / (pow(2.0, log(2.0)))));
     * m - Number of bits in the filter
     * n - Number of items in the filter
     * p - Probability of false positives, float between 0 and 1 or a number indicating 1-in-p
     *
     * @param int $setSize
     * @param float $falsePositiveProbability
     * @return int
     */
    public static function optimalBitSize($setSize, $falsePositiveProbability = 0.001)
    {
        if ($falsePositiveProbability <= 0 || $falsePositiveProbability >= 1) {
            throw new \RangeException('False positive probability must be between 0 and 1');
        }
        return (int) round((($setSize * log($falsePositiveProbability)) / pow(log(2), 2)) * -1);
    }

    /**
     * k = round(log(2.0) * m / n);
     * k - Number of hash functions
     * m - Number of bits in the filter
     * n - Number of items in the filter
     *
     * @param int $setSize
     * @param int $bitSize
     * @return int
     */
    public static function optimalHashCount($setSize, $bitSize)
    {
        return (int) round(($bitSize / $setSize) * log(2));
    }

    /**
     * @param string $value
     * @return array
     */
    private function getBits($value)
    {
        $bits = [];
        /** @var Hash $hash */
        foreach ($this->hashes as $index => $hash) {
            $bits[] = $this->hash($hash, $value, $index);
        }

        return $bits;
    }

    /**
     * @param Hash $hash
     * @param string $value
     * @param int $index
     * @return int
     */
    private function hash(Hash $hash, $value, $index)
    {
        return crc32($hash->hash($value . $index)) % $this->size;
    }
}
