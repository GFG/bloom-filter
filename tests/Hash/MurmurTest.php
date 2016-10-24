<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use RocketLabs\BloomFilter\Hash\Murmur;

class MurmurTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function hash()
    {
        $hash = new Murmur();
        $value = 'test value';
        $expected = 932882152;

        $this->assertEquals($expected, $hash->hash($value));
    }
}
