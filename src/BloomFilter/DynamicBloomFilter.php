<?php

namespace RocketLabs\BloomFilter;
use RocketLabs\BloomFilter\Hash\HashInterface;
use RocketLabs\BloomFilter\Persist\PersisterInterface;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
class DynamicBloomFilter extends BloomFilterAbstract
{
    /** @var int */
    protected $currentSetSize;

    /**
     * @param PersisterInterface $persister
     * @param HashInterface $hash
     * @param $setSize
     * @param int $currentSetSize
     * @param float $falsePositiveProbability
     */
    public function __construct(PersisterInterface $persister, HashInterface $hash, $setSize, $currentSetSize = 0, $falsePositiveProbability = 0.001)
    {
        $this->persister = $persister;
        $this->hash = $hash;

        $this->setCurrentSetSize($currentSetSize);
        $this->setSize($setSize);
        $this->setFalsePositiveProbability($falsePositiveProbability);
        $this->bitSize = $this->getOptimalBitSize((int) $setSize, $falsePositiveProbability);
        $this->hashCount = $this->getOptimalHashCount($setSize, $this->bitSize);
    }

    /**
     * @param int $currentSetSize
     */
    public function setCurrentSetSize($currentSetSize)
    {
        $this->currentSetSize = $currentSetSize;
    }

    /**
     * @inheritdoc
     */
    public function add($value)
    {
        $this->currentSetSize++;
        $this->persister->setBulk($this->getBits($value, floor($this->currentSetSize / $this->setSize)));
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addBulk(array $valueList)
    {
        $bits = [];
        foreach ($valueList as $value) {
            $this->currentSetSize++;
            $bits[] = $this->getBits($value, floor($this->currentSetSize / $this->setSize));
        }
        $this->persister->setBulk(call_user_func_array('array_merge', $bits));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function has($value)
    {
        $bloomFilterCount = floor($this->currentSetSize / $this->setSize);
        $result = false;
        $bits = $this->getBits($value);

        for ($i = 0; $i <= $bloomFilterCount; ++$i) {

            $result = !in_array(
                0,
                $this->persister->getBulk(array_map(
                        function($bit) use ($i) {
                            return $bit + ($i * $this->bitSize);
                        },
                        $bits
                    )
                )
            );

            if ($result) {
                return true;
            }
        }

        return $result;
    }
}
