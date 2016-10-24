<?php

namespace RocketLabs\BloomFilter\Hash;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
class Jenkins implements Hash
{
    /**
     * @inheritdoc
     */
    public function hash($value)
    {
        $key = (string) $value;
        $len = strlen($key);
        for ($hash = $i = 0; $i < $len; ++$i) {
            $hash += ord($key[$i]);
            $hash += ($hash << 10);
            $hash ^= ($hash >> 6);
        }
        $hash += ($hash << 3);
        $hash ^= ($hash >> 11);
        $hash += ($hash << 15);
        return str_pad(dechex($hash), 16, 0, STR_PAD_LEFT);
    }
}
