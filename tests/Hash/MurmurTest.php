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
        $expected = 3804435892;

        $this->assertEquals($expected, $hash->generate($value));
    }
}
