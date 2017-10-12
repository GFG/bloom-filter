<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\BloomFilter;
use RocketLabs\BloomFilter\Hash\HashInterface;
use RocketLabs\BloomFilter\Hash\Murmur;
use RocketLabs\BloomFilter\Persist\PersisterInterface;
use RocketLabs\BloomFilter\Persist\Redis;

class BloomFilterTest extends TestCase
{

    /**
     * @param int $size
     * @param float $probability
     * @param int $expectedHashSize
     * @param int $expectedBitSize
     *
     * @test
     * @dataProvider createBloomFilterDataProvider
     */
    public function createBloomFilter($size, $probability, $expectedHashSize, $expectedBitSize)
    {
        $redisMock = $this->getMockBuilder(\Redis::class)->getMock();
        $persister = new Redis($redisMock, Redis::DEFAULT_KEY);
        $hash = new Murmur();

        $class = new \ReflectionClass("RocketLabs\\BloomFilter\\BloomFilter");
        $propertyHashes = $class->getProperty('hashCount');
        $propertyBitSize = $class->getProperty('bitSize');
        $propertyHashes->setAccessible(true);
        $propertyBitSize->setAccessible(true);
        $filter = new BloomFilter($persister, $hash);
        $filter->setSize($size)->setFalsePositiveProbability($probability);

        static::assertEquals($expectedHashSize, $propertyHashes->getValue($filter));
        static::assertEquals($expectedBitSize, $propertyBitSize->getValue($filter));
    }

    /**
     * @return array
     */
    public function createBloomFilterDataProvider()
    {
        return [
            'Size: 100, probability: 99.9%' => [
                '$size' => 100,
                '$probability' => 0.001,
                '$expectedHashSize' => 10,
                '$expectedBitSize' => 1438
            ],
            'Size: 1000, probability: 99%' => [
                '$size' => 1000,
                '$probability' => 0.01,
                '$expectedHashSize' => 7,
                '$expectedBitSize' => 9585
            ],
            'Size: 1000, probability: 99,99%' => [
                '$size' => 1000,
                '$probability' => 0.0001,
                '$expectedHashSize' => 13,
                '$expectedBitSize' => 19170
            ],
            'Size: 1000000, probability: 99,99%' => [
                '$size' => 1000000,
                '$probability' => 0.0001,
                '$expectedHashSize' => 13,
                '$expectedBitSize' => 19170117 //2.3Mb
            ]
        ];
    }

    /**
 * @test
 */
    public function addToFilter()
    {
        $persister = $this->getMockBuilder(PersisterInterface::class)->getMock();
        $hash = $this->getMockBuilder(HashInterface::class)->getMock();
        $hash->expects($this->exactly(3))
            ->method('generate')
            ->will($this->onConsecutiveCalls(42, 1000, 10048));

        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 232]); //calculated bits for hashes

        $filter = new BloomFilter($persister, $hash);
        $filter->setSize(1024)->setFalsePositiveProbability(0.1);
        $filter->add('testString');
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function filterHasNotBeenInitialized()
    {
        $persister = $this->getMockBuilder(PersisterInterface::class)->getMock();
        $hash = $this->getMockBuilder(HashInterface::class)->getMock();
        $hash->expects($this->never())
            ->method('generate');


        $persister->expects($this->never())
            ->method('setBulk');


        $filter = new BloomFilter($persister, $hash);
        $filter->add('testString');
    }

    /**
     * @test
     */
    public function addBulkFilter()
    {
        $persister = $this->getMockBuilder(PersisterInterface::class)->getMock();
        $hash = $this->getMockBuilder(HashInterface::class)->getMock();
        $hash->expects($this->exactly(9))
            ->method('generate')
            ->will( $this->onConsecutiveCalls(42, 43, 44, 1, 2, 3, 10001, 10002, 10003));

        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 43, 44, 1, 2, 3, 185, 186, 187]); //calculated bits for hashes

        $filter = new BloomFilter($persister, $hash);
        $filter->setSize(1024)->setFalsePositiveProbability(0.1);
        $filter->addBulk(
            ['test String 1',
            'test String 2',
            'test String 3',
            ]
        );
    }

    /**
     * @test
     */
    public function existsInFilter()
    {
        $persister = $this->getMockBuilder(PersisterInterface::class)->getMock();

        $hash = $this->getMockBuilder(HashInterface::class)->getMock();
        $hash->expects($this->any())
            ->method('generate')
            ->will( $this->onConsecutiveCalls(42, 1000, 10001, 42, 1000, 10001));

        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 185]); //calculated bits for hashes
        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 1, 1])
            ->with([42, 1000, 185]); //calculated bits for hashes

        $filterForSet = new BloomFilter($persister, $hash);
        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('testString');

        $filterForGet = new BloomFilter($persister, $hash);
        $filterForGet->setSize(1024)->setFalsePositiveProbability(0.1);
        static::assertTrue($filterForGet->has('testString'));
    }

    /**
     * @test
     */
    public function suspendRestoreFilter()
    {
        $persister = $this->getMockBuilder(PersisterInterface::class)->getMock();

        $hash = $this->getMockBuilder(HashInterface::class)->getMock();
        $hash->expects($this->any())
            ->method('generate')
            ->will( $this->onConsecutiveCalls(42, 1000, 10001, 42, 1000, 10001));

        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 185]); //calculated bits for hashes
        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 1, 1])
            ->with([42, 1000, 185]); //calculated bits for hashes

        $filterForSet = new BloomFilter($persister, $hash);
        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('testString');
        $memento = $filterForSet->suspend();

        $filterForGet = new BloomFilter($persister, $hash);
        $filterForGet->restore($memento);
        static::assertTrue($filterForGet->has('testString'));
    }

    /**
     * @test
     */
    public function DoesNotExistInFilter()
    {
        $persister = $this->getMockBuilder(PersisterInterface::class)->getMock();
        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([42, 1000, 232]); //calculated bits for hashes
        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 0, 1])
            ->with([43, 1001, 233]); //calculated bits for hashes

        $hash = $this->getMockBuilder(HashInterface::class)->getMock();
        $hash->expects($this->exactly(6))
            ->method('generate')
            ->will( $this->onConsecutiveCalls(42, 1000, 10048, 43, 1001, 10049));

        $filterForSet = new BloomFilter($persister, $hash);
        $filterForSet->setSize(1024)->setFalsePositiveProbability(0.1);
        $filterForSet->add('test String');

        $filterForGet = new BloomFilter($persister, $hash);
        $filterForGet->setSize(1024)->setFalsePositiveProbability(0.1);
        static::assertFalse($filterForGet->has('Not Existing test String'));
    }
}
