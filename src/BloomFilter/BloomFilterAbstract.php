<?php

namespace RocketLabs\BloomFilter;

use RocketLabs\BloomFilter\Hash\HashInterface;
use RocketLabs\BloomFilter\Persist\PersisterInterface;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
abstract class BloomFilterAbstract implements BloomFilterInterface
{
    /** @var int */
    protected $bitSize;
    /** @var int */
    protected $hashCount;
    /** @var PersisterInterface */
    protected $persister;
    /** @var HashInterface */
    protected $hash;
    /** @var int */
    protected $setSize;
    /** @var float */
    protected $falsePositiveProbability;

    /**
     * @param int $setSize
     */
    public function setSize($setSize)
    {
        $this->setSize = (int) $setSize;
    }

    /**
     * @param float $falsePositiveProbability
     */
    public function setFalsePositiveProbability($falsePositiveProbability)
    {
        if ($falsePositiveProbability <= 0 || $falsePositiveProbability >= 1) {
            throw new \RangeException('False positive probability must be between 0 and 1');
        }
    }

    /**
     * @param string $value
     * @param int $offset
     * @return array
     */
    protected function getBits($value, $offset = 0)
    {
        $bits = [];

        for ($i = 0; $i < $this->hashCount; $i++) {
            $bits[] = $this->hash($value, $i);
        }

        if ($offset === 0) {
            return $bits;
        } else {
            return array_map(
                function($bit) use ($offset) {
                    return $bit + ($offset * $this->bitSize);
                },
                $bits
            );
        }
    }

    /**
     * @param string $value
     * @param int $index
     *
     * @return int
     */
    protected function hash($value, $index)
    {
        return $this->hash->generate($value . $index) % $this->bitSize;
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
    protected function getOptimalBitSize($setSize, $falsePositiveProbability = 0.001)
    {
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
    protected function getOptimalHashCount($setSize, $bitSize)
    {
        return (int) round(($bitSize / $setSize) * log(2));
    }
}
