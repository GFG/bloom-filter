<?php

namespace RocketLabs\BloomFilter\Hash;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
interface HashInterface
{
    /**
     * @param $value
     * @return string
     */
    public function generate($value);
}
