<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Hash\Crc32b;

class Crc32bTest extends TestCase
{
    /**
     * @test
     */
    public function hash()
    {
        $hash = new Crc32b();
        $value = 'test value';
        $expected = 3973923115;

        static::assertEquals($expected, $hash->generate($value));
    }
}
