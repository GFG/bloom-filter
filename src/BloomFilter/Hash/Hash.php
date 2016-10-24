<?php

namespace RocketLabs\BloomFilter\Hash;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface Hash
{
    /**
     * @param $value
     * @return string
     */
    public function hash($value);
}
