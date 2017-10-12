<?php

namespace RocketLabs\BloomFilter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface RestorableInterface
{
    /**
     * @return Memento
     */
    public function suspend(): Memento;

    /**
     * @param Memento $memento
     */
    public function restore(Memento $memento);
}
