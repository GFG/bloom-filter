<?php

namespace RocketLabs\BloomFilter\Persist;

use RocketLabs\BloomFilter\Exception\InvalidValue;
use RocketLabs\BloomFilter\Exception\MaxLimitPerBitReached;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
class CountString15 implements CountPersister
{
    const DEFAULT_BYTE_SIZE = 1024;
    const MAX_AMOUNT_PER_BIT = 15;

    /** @var string */
    private $bytes;
    /** @var int */
    private $size;

    /**
     * @param string $str
     * @return CountString15
     */
    public static function createFromString($str)
    {
        $instance = new static();
        $instance->bytes = $str;
        $instance->size = strlen($str);
        return $instance;
    }

    public function __construct()
    {
        $this->size = self::DEFAULT_BYTE_SIZE;
        $this->bytes = str_repeat(chr(0), $this->size);
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        $this->size = self::DEFAULT_BYTE_SIZE;
        $this->bytes = str_repeat(chr(0), $this->size);
    }

    public function incrementBulk(array $bits): array
    {
        $result = [];

        foreach ($bits as $bit) {
            $result[$bit] = $this->incrementBit($bit);
        }

        return $result;
    }

    /**
     * @param int $bit
     * @return int
     */
    public function incrementBit(int $bit): int
    {
        $offsetByte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$offsetByte]);

        $low = $byte & 0x0F;
        $high = ($byte >> 4) & 0x0F;

        if ($bit & 1) {
            $return = ++$high;
        } else {
            $return = ++$low;
        }

        if ($low > self::MAX_AMOUNT_PER_BIT || $high > self::MAX_AMOUNT_PER_BIT) {
            throw new MaxLimitPerBitReached('max amount per bit should not be higher than ' . self::MAX_AMOUNT_PER_BIT);
        }

        $this->bytes[$offsetByte] = chr($low | ($high << 4));

        return $return;
    }

    /**
     * @param int $bit
     * @return int
     */
    public function get(int $bit): int
    {
        $offsetByte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$offsetByte]);

        $low = $byte & 0x0F;
        $high = ($byte >> 4) & 0x0F;

        if ($bit & 1) {
            return $high;
        } else {
            return $low;
        }
    }

    /**
     * @param int $bit
     * @return int
     */
    public function decrementBit(int $bit): int
    {
        $offsetByte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$offsetByte]);

        $low = $byte & 0x0F;
        $high = ($byte >> 4) & 0x0F;

        if ($bit & 1) {
            $return = --$high;
        } else {
            $return = --$low;
        }

        if ($low > self::MAX_AMOUNT_PER_BIT || $high > self::MAX_AMOUNT_PER_BIT) {
            throw new MaxLimitPerBitReached('max amount per bit should not be higher than ' . self::MAX_AMOUNT_PER_BIT);
        }

        $this->bytes[$offsetByte] = chr(max([$low, 0]) | (max([$high, 0]) << 4));

        return max([$return, 0]);
    }

    /**
     * @param int $value
     */
    private function assertOffset(int $value)
    {
        if ($value < 0) {
            throw new InvalidValue('Value must be greater than zero.');
        }
    }

    /**
     * @param int $offset
     * @return int
     */
    private function offsetToByte(int $offset): int
    {
         $this->assertOffset($offset);
         $byte = $offset / 2;

        if ($this->size <= $byte) {
            $this->bytes .= str_repeat(chr(0), $byte - $this->size + self::DEFAULT_BYTE_SIZE);
            $this->size = strlen($this->bytes);
        }

        return $byte;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->bytes;
    }
}
