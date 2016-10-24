<?php

namespace RocketLabs\BloomFilter\Hash;

/**
 * @author Igor Veremchuk igor.veremchuk@gmail.com
 */
class Crc32b implements Hash
{
    /**
     * @inheritdoc
     */
    public function hash($value)
    {
        return sprintf('%u', hexdec(hash('crc32b', $value)));
    }
}
