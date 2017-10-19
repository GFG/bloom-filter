<?php

namespace RocketLabs\BloomFilter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface Resetable
{
    /**
     * @return Memento
     */
    public function reset();
}
