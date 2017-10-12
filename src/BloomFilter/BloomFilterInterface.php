<?php

namespace RocketLabs\BloomFilter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface BloomFilterInterface
{
    /**
     * @param string $value
     * @return BloomFilterInterface
     */
    public function add(string $value): BloomFilterInterface;

    /**
     * @param array $valueList
     * @return BloomFilterInterface
     */
    public function addBulk(array $valueList): BloomFilterInterface;

    /**
     * @param string $value
     * @return bool
     */
    public function has(string $value): bool;
}
