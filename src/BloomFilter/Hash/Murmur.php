<?php

namespace RocketLabs\BloomFilter\Hash;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
class Murmur implements Hash
{
    /**
     * @inheritdoc
     */
    public function hash($value)
    {
        $m = 0x5bd1e995;
        $r = 24;
        $seed = 0;
        $len = strlen($value);
        $h = $seed ^ $len;
        $o = 0;

        while($len >= 4) {
            $k = ord($value[$o]) | (ord($value[$o+1]) << 8) | (ord($value[$o+2]) << 16) | (ord($value[$o+3]) << 24);
            $k = ($k * $m) & 4294967295;
            $k = ($k ^ ($k >> $r)) & 4294967295;
            $k = ($k * $m) & 4294967295;

            $h = ($h * $m) & 4294967295;
            $h = ($h ^ $k) & 4294967295;

            $o += 4;
            $len -= 4;
        }

        $data = substr($value,0 - $len,$len);

        switch($len) {
            case 3: $h = ($h ^ (ord($data[2]) << 16)) & 4294967295;
            case 2: $h = ($h ^ (ord($data[1]) << 8)) & 4294967295;
            case 1: $h = ($h ^ (ord($data[0]))) & 4294967295;
                $h = ($h * $m) & 4294967295;
        };
        $h = ($h ^ ($h >> 13)) & 4294967295;
        $h = ($h * $m) & 4294967295;
        $h = ($h ^ ($h >> 15)) & 4294967295;

        return $h;
    }
}
