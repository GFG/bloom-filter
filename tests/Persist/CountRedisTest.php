<?php

namespace RocketLabs\BloomFilter\Test\Persist;

use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\Exception\InvalidCounter;
use RocketLabs\BloomFilter\Persist\CountRedis;

class CountRedisTest extends TestCase
{
    /**
     * @test
     */
    public function incrementBit()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('hIncrBy')
            ->willReturn(1)
            ->with(CountRedis::DEFAULT_KEY, 100, 1);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, CountRedis::DEFAULT_KEY);
        self::assertEquals(1, $persister->incrementBit(100));
    }

    /**
     * @test
     */
    public function decrementBit()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('hIncrBy')
            ->willReturn(0)
            ->with(CountRedis::DEFAULT_KEY, 100, -1);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, CountRedis::DEFAULT_KEY);
        self::assertEquals(0, $persister->decrementBit(100));
    }

    /**
     * @test
     */
    public function getBit()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('hGet')
            ->willReturn(10)
            ->with(CountRedis::DEFAULT_KEY, 100);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, CountRedis::DEFAULT_KEY);
        self::assertEquals(10, $persister->get(100));
    }


    /**
     * @test
     * @expectedException  \RuntimeException
     */
    public function negativeDecrement()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('hIncrBy')
            ->willReturn(-1)
            ->with(CountRedis::DEFAULT_KEY, 100, -1);
        $redisMock->expects($this->once())
            ->method('hSet')
            ->willReturn(0)
            ->with(CountRedis::DEFAULT_KEY, 100, 0);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, CountRedis::DEFAULT_KEY);

        $this->expectException(InvalidCounter::class);

        self::assertEquals(0, $persister->decrementBit(100));
    }

    /**
     * @test
     */
    public function reset()
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $redisMock->expects($this->once())
            ->method('del')
            ->willReturn(1)
            ->with(CountRedis::DEFAULT_KEY);
        /** @var \Redis $redisMock */
        $persister = new CountRedis($redisMock, CountRedis::DEFAULT_KEY);
        $persister->reset(100);
    }
}
