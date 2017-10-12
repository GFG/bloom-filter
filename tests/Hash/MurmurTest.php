<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Hash\Murmur;

class MurmurTest extends TestCase
{
    /**
     * @test
     */
    public function hash()
    {
        $hash = new Murmur();
        $value = 'test value';
        $expected = 3804435892;

        static::assertEquals($expected, $hash->generate($value));
    }
}
