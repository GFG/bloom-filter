<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use RocketLabs\BloomFilter\Hash\Fnv;

class FnvTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function hash()
    {
        $hash = new Fnv();
        $value = 'test value';
        $expected =  hexdec(hash('fnv132', $value));

        $this->assertEquals($expected, $hash->hash($value));
    }
}
