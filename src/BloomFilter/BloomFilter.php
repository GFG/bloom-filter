<?php

namespace RocketLabs\BloomFilter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
class BloomFilter extends BloomFilterAbstract implements RestorableInterface
{
    /**
     * @inheritdoc
     */
    public function add($value)
    {
        $this->assertInit();
        $this->persister->setBulk($this->getBits($value));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addBulk(array $valueList)
    {
        $this->assertInit();
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
        $this->assertInit();
        $bits = $this->persister->getBulk($this->getBits($value));

        return !in_array(0, $bits);
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
            ->addParam('probability', $this->falsePositiveProbability);

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
        $this->bitSize = $this->getOptimalBitSize($this->setSize, $this->falsePositiveProbability);
        $this->hashCount = $this->getOptimalHashCount($this->setSize, $this->bitSize);
    }

    /**
     * @param Memento $memento
     */
    private function checkIntegrity(Memento $memento)
    {
        if ($memento->getHashClass() != get_class($this->hash)) {
            throw new \RuntimeException('wrong hash class');
        }

        if ($memento->getParam('set_size') === null) {
            throw new \RuntimeException('Memento object has not "set_size" parameter');
        }

        if ($memento->getParam('probability') === null) {
            throw new \RuntimeException('Memento object has not "probability" parameter');
        }
    }
}
