<?php

namespace RocketLabs\BloomFilter\Test\Persist;

use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Persist\BitRedis;

class BitRedisTest extends TestCase
{

    /**
     * @test
     */
    public function setBit()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('setBit')
            ->willReturn(1)
            ->with(BitRedis::DEFAULT_KEY, 100, 1);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->set(100);
    }

    /**
     * @test
     */
    public function unsetBit()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('setBit')
            ->willReturn(1)
            ->with(BitRedis::DEFAULT_KEY, 100, 0);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->unset(100);
    }

    /**
     * @test
     */
    public function getBit()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('getBit')
            ->willReturn(0)
            ->with(BitRedis::DEFAULT_KEY, 100);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        static::assertEquals(0, $persister->get(100));
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function setNegativeBit()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->set(-1);
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function getNegativeBit()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->set(-1);
        $persister->set(-1);

    }

    /**
     * @test
     * @expectedException \TypeError
     */
    public function getWrongBitValue()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->set('test');
    }


    /**
     * @test
     */
    public function setBits()
    {
        $bits = [2, 16, 250];
        $pipeMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('pipeline')
            ->willReturn($pipeMock);

        $pipeMock->expects($this->exactly(count($bits)))
            ->method('setBit')
            ->withConsecutive(
                [BitRedis::DEFAULT_KEY, $bits[0], 1],
                [BitRedis::DEFAULT_KEY, $bits[1], 1],
                [BitRedis::DEFAULT_KEY, $bits[2], 1]
            )
            ->willReturn(1);

        $pipeMock->expects($this->once())
            ->method('exec')
            ->willReturn(1);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->setBulk($bits);
    }

    /**
     * @test
     */
    public function unsetBits()
    {
        $bits = [2, 16, 250];
        $pipeMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('pipeline')
            ->willReturn($pipeMock);

        $pipeMock->expects($this->exactly(count($bits)))
            ->method('setBit')
            ->withConsecutive(
                [BitRedis::DEFAULT_KEY, $bits[0], 0],
                [BitRedis::DEFAULT_KEY, $bits[1], 0],
                [BitRedis::DEFAULT_KEY, $bits[2], 0]
            )
            ->willReturn(1);

        $pipeMock->expects($this->once())
            ->method('exec')
            ->willReturn(1);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->unsetBulk($bits);
    }

    /**
     * @test
     */
    public function reset()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('del');

        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->reset();
    }

    /**
     * @test
     */
    public function getBits()
    {
        $bits = [2, 16, 250];
        $pipeMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('pipeline')
            ->willReturn($pipeMock);

        $pipeMock->expects($this->exactly(count($bits)))
            ->method('getBit')
            ->withConsecutive(
                [BitRedis::DEFAULT_KEY, $bits[0]],
                [BitRedis::DEFAULT_KEY, $bits[1]],
                [BitRedis::DEFAULT_KEY, $bits[2]]
            )
            ->willReturn([1,1,1]);

        $pipeMock->expects($this->once())
            ->method('exec')
            ->willReturn([1]);
        /** @var \Redis $redisMock */
        $persister = new BitRedis($redisMock, BitRedis::DEFAULT_KEY);
        $persister->getBulk($bits);
    }
}
