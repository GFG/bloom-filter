<?php

namespace RocketLabs\BloomFilter;

use RocketLabs\BloomFilter\Exception\CannotRestore;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
class DynamicBloomFilter extends BloomFilterAbstract implements RestorableInterface
{
    /** @var int */
    protected $currentSetSize = 0;

    /**
     * @param int $currentSetSize
     *
     * @return BloomFilterInterface
     */
    public function setCurrentSetSize(int $currentSetSize): BloomFilterInterface
    {
        $this->currentSetSize = $currentSetSize;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function add(string $value): BloomFilterInterface
    {
        $this->assertInit();
        $this->currentSetSize++;
        $this->persister->setBulk($this->getBits($value, floor($this->currentSetSize / $this->setSize)));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addBulk(array $valueList): BloomFilterInterface
    {
        $this->assertInit();
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
    public function has(string $value): bool
    {
        $this->assertInit();
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

    /**
     * @inheritdoc
     */
    public function suspend(): Memento
    {
        $this->assertInit();
        $memento = new Memento();
        $memento->setHashClass(get_class($this->hash))
            ->addParam('set_size', $this->setSize)
            ->addParam('probability', $this->falsePositiveProbability)
            ->addParam('current_set_size', $this->currentSetSize);

        return $memento;
    }

    /**
     * @inheritdoc
     */
    public function restore(Memento $memento)
    {
        $this->checkIntegrity($memento);
        $this->setSize($memento->getParam('set_size'));
        $this->setFalsePositiveProbability($memento->getParam('probability'));
        $this->setCurrentSetSize($memento->getParam('current_set_size'));
        $this->bitSize = $this->getOptimalBitSize($this->setSize, $this->falsePositiveProbability);
        $this->hashCount = $this->getOptimalHashCount($this->setSize, $this->bitSize);
    }

    /**
     * @param Memento $memento
     */
    private function checkIntegrity(Memento $memento)
    {
        if ($memento->getHashClass() != get_class($this->hash)) {
            throw new CannotRestore('Memento object should have same hash class as object');
        }

        if ($memento->getParam('set_size') === null) {
            throw new CannotRestore('Memento object has not "set_size" parameter');
        }

        if ($memento->getParam('probability') === null) {
            throw new CannotRestore('Memento object has not "probability" parameter');
        }

        if ($memento->getParam('current_set_size') === null) {
            throw new CannotRestore('Memento object has not "current_set_size" parameter');
        }
    }
}
