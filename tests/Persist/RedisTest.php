<?php

namespace RocketLabs\BloomFilter\Test\Persist;

use RocketLabs\BloomFilter\Persist\Redis;

class RedisTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function setBit()
    {
        $redisMock = $this->getMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('setBit')
            ->willReturn(1)
            ->with(Redis::DEFAULT_KEY, 100, 1);
        /** @var \Redis $redisMock */
        $persister = new Redis($redisMock, Redis::DEFAULT_KEY);
        $persister->set(100);
    }

    /**
     * @test
     */
    public function getBit()
    {
        $redisMock = $this->getMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('getBit')
            ->willReturn(0)
            ->with(Redis::DEFAULT_KEY, 100);
        /** @var \Redis $redisMock */
        $persister = new Redis($redisMock, Redis::DEFAULT_KEY);
        $this->assertEquals(0, $persister->get(100));
    }

    /**
     * @test
     * @expectedException \RangeException
     */
    public function setNegativeBit()
    {
        $redisMock = $this->getMock(\Redis::class);
        /** @var \Redis $redisMock */
        $persister = new Redis($redisMock, Redis::DEFAULT_KEY);
        $persister->set(-1);
    }

    /**
     * @test
     * @expectedException \RangeException
     */
    public function getNegativeBit()
    {
        $redisMock = $this->getMock(\Redis::class);
        /** @var \Redis $redisMock */
        $persister = new Redis($redisMock, Redis::DEFAULT_KEY);
        $persister->set(-1);
        $persister->set(-1);

    }

    /**
     * @test
     * @expectedException \UnexpectedValueException
     */
    public function getWrongBitValue()
    {
        $redisMock = $this->getMock(\Redis::class);
        /** @var \Redis $redisMock */
        $persister = new Redis($redisMock, Redis::DEFAULT_KEY);
        $persister->set('test');
    }


    /**
     * @test
     */
    public function setBits()
    {
        $bits = [2, 16, 250];
        $pipeMock = $this->getMock(\Redis::class);
        $redisMock = $this->getMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('pipeline')
            ->willReturn($pipeMock);

        $pipeMock->expects($this->exactly(count($bits)))
            ->method('setBit')
            ->withConsecutive(
                [Redis::DEFAULT_KEY, $bits[0], 1],
                [Redis::DEFAULT_KEY, $bits[1], 1],
                [Redis::DEFAULT_KEY, $bits[2], 1]
            )
            ->willReturn(1);

        $pipeMock->expects($this->once())
            ->method('exec')
            ->willReturn(1);
        /** @var \Redis $redisMock */
        $persister = new Redis($redisMock, Redis::DEFAULT_KEY);
        $persister->setBulk($bits);
    }

    /**
     * @test
     */
    public function getBits()
    {
        $bits = [2, 16, 250];
        $pipeMock = $this->getMock(\Redis::class);
        $redisMock = $this->getMock(\Redis::class);
        $redisMock->expects($this->once())
            ->method('pipeline')
            ->willReturn($pipeMock);

        $pipeMock->expects($this->exactly(count($bits)))
            ->method('getBit')
            ->withConsecutive(
                [Redis::DEFAULT_KEY, $bits[0]],
                [Redis::DEFAULT_KEY, $bits[1]],
                [Redis::DEFAULT_KEY, $bits[2]]
            )
            ->willReturn([1,1,1]);

        $pipeMock->expects($this->once())
            ->method('exec')
            ->willReturn(1);
        /** @var \Redis $redisMock */
        $persister = new Redis($redisMock, Redis::DEFAULT_KEY);
        $persister->getBulk($bits);
    }
}
