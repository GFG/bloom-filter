<?php

namespace RocketLabs\BloomFilter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface ResetableInterface
{
    /**
     * @return Memento
     */
    public function reset();
}
