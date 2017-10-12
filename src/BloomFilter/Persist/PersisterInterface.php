<?php

namespace RocketLabs\BloomFilter\Persist;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface PersisterInterface
{
    /**
     * @param array $bits
     * @return int[]
     */
    public function getBulk(array $bits): array;

    /**
     * @param int $bit
     * @return int
     */
    public function get(int $bit): int;

    /**
     * @param array $bits
     * @return void
     */
    public function setBulk(array $bits);

    /**
     * @param int $bit
     * @return void
     */
    public function set(int $bit);

    /**
     * @return void
     */
    public function reset();
}