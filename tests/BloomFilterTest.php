<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use RocketLabs\BloomFilter\BloomFilter;
use RocketLabs\BloomFilter\Persist\Redis;

class BloomFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function optimalBitSize()
    {
        $n = 100; // Number fo items
        $p = 0.001; // Probability of false positives

        $this->assertEquals(
            round((($n * log($p)) / pow(log(2), 2)) * -1),
            BloomFilter::optimalBitSize($n, $p)
        );
    }

    /**
     * @test
     */
    public function optimalHashCount()
    {
        $n = 100; // Number fo items
        $m = 1024; // Number fo bits

        $this->assertEquals(
            (int) round(($n / $m) * log(2)),
            BloomFilter::optimalHashCount($m, $n)
        );
    }

    /**
     * @param int $size
     * @param float $probability
     * @param int $expectedHashSize
     * @param int $expectedBitSize
     *
     * @test
     * @dataProvider createFromApproximateSizeDataProvider
     */
    public function createFromApproximateSize($size, $probability, $expectedHashSize, $expectedBitSize)
    {
        $redisMock = $this->getMock(\Redis::class);
        $persister = new Redis($redisMock, Redis::DEFAULT_KEY);

        $class = new \ReflectionClass("RocketLabs\\BloomFilter\\BloomFilter");
        $propertyHashes = $class->getProperty('hashes');
        $propertySize = $class->getProperty('size');
        $propertyHashes->setAccessible(true);
        $propertySize->setAccessible(true);
        $filter = BloomFilter::createFromApproximateSize($persister, $size, $probability);

        $this->assertEquals($expectedHashSize, count($propertyHashes->getValue($filter)));
        $this->assertEquals($expectedBitSize, $propertySize->getValue($filter));
    }

    /**
     * @return array
     */
    public function createFromApproximateSizeDataProvider()
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
        $persister = $this->getMock('RocketLabs\BloomFilter\Persist\Persister');
        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([687, 549, 684]); //calculated bits for hashes

        $filter = new BloomFilter($persister, 1024, 3);
        $filter->add('testString');
    }

    /**
     * @test
     */
    public function addBulkFilter()
    {
        $persister = $this->getMock('RocketLabs\BloomFilter\Persist\Persister');
        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([ 572, 177, 442, 128, 451, 157, 905, 698, 186]); //calculated bits for hashes

        $filter = new BloomFilter($persister, 1024, 3);
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
        $persister = $this->getMock('RocketLabs\BloomFilter\Persist\Persister');
        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([687, 549, 684]); //calculated bits for hashes
        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 1, 1])
            ->with([687, 549, 684]); //calculated bits for hashes

        $filterForSet = new BloomFilter($persister, 1024, 3);
        $filterForSet->add('testString');

        $filterForGet = new BloomFilter($persister, 1024, 3);
        $this->assertTrue($filterForGet->has('testString'));
    }

    /**
     * @test
     */
    public function DoesNotExistInFilter()
    {
        $persister = $this->getMock('RocketLabs\BloomFilter\Persist\Persister');
        $persister->expects($this->once())
            ->method('setBulk')
            ->willReturn(1)
            ->with([1008, 193, 573]); //calculated bits for hashes
        $persister->expects($this->once())
            ->method('getBulk')
            ->willReturn([1, 0, 1])
            ->with([682, 79, 401]); //calculated bits for hashes

        $filterForSet = new BloomFilter($persister, 1024, 3);
        $filterForSet->add('test String');

        $filterForGet = new BloomFilter($persister, 1024, 3);
        $this->assertFalse($filterForGet->has('Not Existing test String'));
    }
}
