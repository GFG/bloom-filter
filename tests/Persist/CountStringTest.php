<?php

namespace RocketLabs\BloomFilter\Test\Persist;

use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Exception\MaxLimitPerBitReached;
use RocketLabs\BloomFilter\Persist\CountString15;

class CountStringTest extends TestCase
{
    /**
     * @test
     */
    public function createWithDefaultSize()
    {
        $persister = new CountString15();

        $class = new \ReflectionClass(CountString15::class);
        $propertyBytes = $class->getProperty("bytes");
        $propertySize = $class->getProperty("size");
        $propertyBytes->setAccessible(true);
        $propertySize->setAccessible(true);

        static::assertEquals(CountString15::DEFAULT_BYTE_SIZE, $propertySize->getValue($persister));
        static::assertEquals(CountString15::DEFAULT_BYTE_SIZE, strlen($propertyBytes->getValue($persister)));
    }

    /**
     * @test
     */
    public function incrementBit()
    {
        $persister = new CountString15();
        static::assertEquals(1, $persister->incrementBit(0));
        static::assertEquals(2, $persister->incrementBit(0));
        static::assertEquals(1, $persister->incrementBit(1));
        static::assertEquals(1, $persister->incrementBit(2));
        static::assertEquals(1, $persister->incrementBit(2048));
        static::assertEquals(2, $persister->incrementBit(2048));
    }

    /**
     * @test
     */
    public function getBit()
    {
        $persister = new CountString15();
        for ($i = 0; $i < 7; $i++) {
            $persister->incrementBit(13);
        }
        $persister->incrementBit(14);
        $persister->incrementBit(10001);
        $persister->decrementBit(10002);

        static::assertEquals(0, $persister->get(12));
        static::assertEquals(7, $persister->get(13));
        static::assertEquals(1, $persister->get(10001));
        static::assertEquals(0, $persister->get(10002));
    }

    /**
     * @test
     */
    public function incrementOverflow()
    {
        $persister = new CountString15();

        $this->expectException(MaxLimitPerBitReached::class);

        for ($i = 0; $i < 16; $i++) {
            $persister->incrementBit(42);
        }
    }

    /**
     * @test
     */
    public function reset()
    {
        $persister = new CountString15();
        $persister->incrementBit(42);
        $persister->reset();

        static::assertEquals(0, $persister->get(42));
    }
}
