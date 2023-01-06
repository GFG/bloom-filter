<?php

namespace RocketLabs\BloomFilter\Test\Hash;

use PHPUnit\Framework\TestCase;
use RocketLabs\BloomFilter\DynamicBloomFilter;
use RocketLabs\BloomFilter\Hash\Hash;
use RocketLabs\BloomFilter\Persist\BitPersister;

class DynamicBloomFilterTest extends TestCase
{

    /**
     * @test
     */
    public function addToDynamicFilter()
    {
        $persister = $this->getMockBuilder(BitPersister::class)->getMock();
        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->any())
            ->method('generate')
            ->willReturn('2');

        $persister->expects($this->exactly(10))
            ->method('setBulk')
            ->withConsecutive(
                [[2, 2, 2]],
                    [[2, 2, 2]],
                    [[16, 16, 16]],
                    [[16, 16, 16]],
                    [[16, 16, 16]],
                    [[30, 30, 30]],
                    [[30, 30, 30]],
                    [[30, 30, 30]],
                    [[44, 44, 44]],
                    [[44, 44, 44]]
            )
        ->willReturn(1);

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }
    }

    /**
     * @test
     */
    public function existsInFilter()
    {
        $persister = $this->getMockBuilder(BitPersister::class)->getMock();
        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->any())
            ->method('generate')
            ->willReturn('2');

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }

        $persister->expects($this->exactly(2))
            ->method('getBulk')
            ->willReturnOnConsecutiveCalls(
                [1, 0, 1],
                [1, 1, 1]
            )
            ->withConsecutive(
                [[2, 2, 2]],
                [[16, 16, 16]]
            );

        static::assertTrue($filter->has('testString'));
    }

    /**
     * @test
     */
    public function suspendRestoreFilter()
    {
        $persister = $this->getMockBuilder(BitPersister::class)->getMock();
        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->any())
            ->method('generate')
            ->willReturn('2');

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }

        $persister->expects($this->exactly(2))
            ->method('getBulk')
            ->willReturnOnConsecutiveCalls(
                [1, 0, 1],
                [1, 1, 1]
            )
            ->withConsecutive(
                [[2, 2, 2]],
                [[16, 16, 16]]
            );

        $memento = $filter->saveState();

        $restoredFilter = new DynamicBloomFilter($persister, $hash);
        $restoredFilter->restoreState($memento);

        static::assertTrue($restoredFilter->has('testString'));
    }

    /**
     * @test
     */
    public function doesNotExistsInFilter()
    {
        $persister = $this->getMockBuilder(BitPersister::class)->getMock();
        $hash = $this->getMockBuilder(Hash::class)->getMock();
        $hash->expects($this->any())
            ->method('generate')
            ->willReturn('2');

        $filter = new DynamicBloomFilter($persister, $hash);
        $filter->setSize(3)->setFalsePositiveProbability(0.1);
        for($i = 0; $i < 10; $i++) {
            $filter->add('testString');
        }

        $persister->expects($this->exactly(4))
            ->method('getBulk')
            ->willReturnOnConsecutiveCalls(
                [1, 0, 1],
                [1, 1, 0],
                [1, 1, 0],
                [1, 1, 0]
            )
            ->withConsecutive(
                [[2, 2, 2]],
                [[16, 16, 16]],
                [[30, 30, 30]],
                [[44, 44, 44]]
            );

        static::assertFalse($filter->has('testString'));
    }
}
