<?php

namespace RocketLabs\BloomFilter\Hash;

/**
 * @author Igor Veremchuk igor.veremchuk@gmail.com
 */
interface Hash
{
    /**
     * @param $value
     * @return string
     */
    public function hash($value);
}
