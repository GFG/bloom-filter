<?php

namespace RocketLabs\BloomFilter\Persist;

/**
 * @author Igor Veremchuk igor.veremchuk@gmail.com
 */
class BitString implements Persister
{
    const BITS_IN_BYTE = 8;
    const DEFAULT_SIZE = 1024;

    /** @var string */
    private $bytes;
    /** @var int */
    private $size;


    /**
     * @param string $str
     * @return BitString
     */
    public static function createFromString($str)
    {
        $instance = new self();
        $instance->bytes = $str;
        $instance->size = strlen($str);
        return $instance;
    }

    public function __construct()
    {
        $this->size = self::DEFAULT_SIZE;
        $this->bytes = str_repeat(chr(0), $this->size);
    }

    /**
     * @inheritdoc
     */
    public function getBulk(array $bits)
    {
        $resultBits = [];
        foreach ($bits as $bit) {
            $resultBits[] = $this->get($bit);
        }

        return $resultBits;
    }

    /**
     * @inheritdoc
     */
    public function setBulk(array $bits)
    {
        foreach ($bits as $bit) {
            $this->set($bit);
        }
    }

    /**
     * @inheritdoc
     */
    public function get($bit)
    {
        $byte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$byte]);
        $bit = (bool) ($this->bitPos($bit) & $byte);

        return (int) $bit;
    }

    /**
     * @inheritdoc
     */
    public function set($bit)
    {
        $offsetByte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$offsetByte]);
        $pos = $this->bitPos($bit);

        $byte |= $pos;
        $this->bytes[$offsetByte] = chr($byte);
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->bytes;
    }

    /**
     * @param int $value
     */
    private function assertOffset($value)
    {
        if (!is_int($value)) {
            throw new \UnexpectedValueException('Value must be an integer.');
        }

        if ($value < 0) {
            throw new \RangeException('Value must be greater than zero.');
        }
    }

    /**
     * @param int $offset
     * @return int
     */
    private function offsetToByte($offset)
    {
        $this->assertOffset($offset);
        $byte = (int) floor($offset / self::BITS_IN_BYTE);

        if ($this->size <= $byte) {
            $this->bytes .= str_repeat(chr(0), $byte - $this->size + self::DEFAULT_SIZE);
            $this->size = strlen($this->bytes);
        }

        return $byte;
    }

    /**
     * @param int $offset
     * @return int
     */
    private function bitPos($offset)
    {
        return (int) pow(2, $offset % self::BITS_IN_BYTE);
    }
}
