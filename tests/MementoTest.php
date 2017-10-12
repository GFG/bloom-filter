<?php

namespace RocketLabs\BloomFilter\Test\Persist;


use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Hash\Murmur;
use RocketLabs\BloomFilter\Hash\Murmur as AnotherHash;
use RocketLabs\BloomFilter\Memento;

class MementoTest extends TestCase
{

    /**
     * @test
     */
    public function setHashClass()
    {
        $memento = new Memento();
        $memento->setHashClass(Murmur::class);
        static::assertEquals(AnotherHash::class, $memento->getHashClass());
    }

    /**
     * @test
     */
    public function setParams()
    {
        $memento = new Memento();
        $memento->addParam('key', 1);

        self::assertEquals(1, $memento->getParam('key'));
        self::assertEquals(null, $memento->getParam('wrong_key'));
    }
}
