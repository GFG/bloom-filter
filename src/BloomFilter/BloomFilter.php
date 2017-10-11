<?php

namespace RocketLabs\BloomFilter;
use RocketLabs\BloomFilter\Hash\HashInterface;
use RocketLabs\BloomFilter\Persist\PersisterInterface;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
class BloomFilter extends BloomFilterAbstract
{

    /**
     * @param PersisterInterface $persister
     * @param HashInterface $hash
     * @param int $setSize
     * @param float $falsePositiveProbability
     */
    public function __construct(PersisterInterface $persister, HashInterface $hash, $setSize, $falsePositiveProbability = 0.001)
    {
        $this->persister = $persister;
        $this->hash = $hash;
        $this->setSize($setSize);
        $this->setFalsePositiveProbability($falsePositiveProbability);

        $this->bitSize = $this->getOptimalBitSize($this->setSize, $falsePositiveProbability);
        $this->hashCount = $this->getOptimalHashCount($this->setSize, $this->bitSize);
    }

    /**
     * @inheritdoc
     */
    public function add($value)
    {
        $this->persister->setBulk($this->getBits($value));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addBulk(array $valueList)
    {
        $bits = [];
        foreach ($valueList as $value) {
            $bits[] = $this->getBits($value);
        }

        $this->persister->setBulk(call_user_func_array('array_merge', $bits));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function has($value)
    {
        $bits = $this->persister->getBulk($this->getBits($value));

        return !in_array(0, $bits);
    }
}
