<?php

namespace RocketLabs\BloomFilter\Test\Persist;

use RocketLabs\BloomFilter\Persist\BitString;

class BitStringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function createWithDefaultSize()
    {
        $persister = new BitString();

        $class = new \ReflectionClass("RocketLabs\\BloomFilter\\Persist\\BitString");
        $propertyBytes = $class->getProperty("bytes");
        $propertySize = $class->getProperty("size");
        $propertyBytes->setAccessible(true);
        $propertySize->setAccessible(true);

        $this->assertEquals(BitString::DEFAULT_BYTE_SIZE, $propertySize->getValue($persister));
        $this->assertEquals(BitString::DEFAULT_BYTE_SIZE, strlen($propertyBytes->getValue($persister)));
    }

    /**
     * @test
     */
    public function setBit()
    {
        $persister = new BitString();
        $persister->set(100);
        $this->assertEquals(1, $persister->get(100));

        $allNotSetBitsAreOff = true;

        for ($i = 0; $i < BitString::DEFAULT_BYTE_SIZE * 8; $i++) {
            if ($i == 100) {
                continue;
            }
            $allNotSetBitsAreOff = $persister->get($i) == 0 && $allNotSetBitsAreOff;
        }

        $this->assertTrue($allNotSetBitsAreOff);

    }

    /**
     * @test
     */
    public function bitIsNotSet()
    {
        $persister = new BitString();
        $allNotSetBitsAreOff = true;

        for ($i = 0; $i < BitString::DEFAULT_BYTE_SIZE * 8; $i++) {
            $allNotSetBitsAreOff = $persister->get($i) == 0 && $allNotSetBitsAreOff;
        }

        $this->assertTrue($allNotSetBitsAreOff);
    }

    /**
     * @test
     * @expectedException \RangeException
     */
    public function setNegativeBit()
    {
        $persister = new BitString();
        $persister->set(-1);
    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function getWrongBitValue()
    {
        $persister = new BitString();
        $persister->set('test');
    }

    /**
     * @test
     */
    public function setBits()
    {
        $bits = [2, 16, 250, 1024];
        $persister = new BitString();
        $persister->setBulk($bits);

        foreach ($bits as $bit) {
            $this->assertEquals(1, $persister->get($bit));
        }
    }

    /**
     * @test
     */
    public function getBits()
    {
        $bits = [2, 16, 250, 1024];
        $persister = new BitString();
        $persister->setBulk($bits);

        $this->assertEquals([1, 1, 1, 1, 0], $persister->getBulk(array_merge($bits, [512])));
    }

    /**
     * @test
     */
    public function increaseSize()
    {
        $persister = new BitString();

        $class = new \ReflectionClass("RocketLabs\\BloomFilter\\Persist\\BitString");
        $propertyBytes = $class->getProperty("bytes");
        $propertySize = $class->getProperty("size");
        $propertyBytes->setAccessible(true);
        $propertySize->setAccessible(true);

        $bit = BitString::DEFAULT_BYTE_SIZE * 8 * 3 + 2;
        $increasedSize =  BitString::DEFAULT_BYTE_SIZE * 3 + BitString::DEFAULT_BYTE_SIZE;

        $persister->set($bit);
        $allNotSetBitsAreOff = true;
         for ($i = 0; $i < $increasedSize; $i++) {
             if ($i == $bit) {
                 continue;
             }
             $allNotSetBitsAreOff = $persister->get($i) == 0 && $allNotSetBitsAreOff;
         }


        $this->assertTrue($allNotSetBitsAreOff);
        $this->assertEquals($increasedSize, $propertySize->getValue($persister));
        $this->assertEquals($increasedSize, strlen($propertyBytes->getValue($persister)));
    }
}
