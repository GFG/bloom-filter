<?php

namespace RocketLabs\BloomFilter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface BloomFilterInterface
{
    /**
     * @param string $value
     * @return $this
     */
    public function add($value);

    /**
     * @param string[] $valueList
     * @return $this
     */
    public function addBulk(array $valueList);

    /**
     * @param string $value
     * @return bool
     */
    public function has($value);
}
