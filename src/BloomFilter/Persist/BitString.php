<?php

namespace RocketLabs\BloomFilter\Persist;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
class BitString implements PersisterInterface
{
    const DEFAULT_BYTE_SIZE = 1024;

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

        return ($byte >> $bit % 8) & 1;
    }

    /**
     * @inheritdoc
     */
    public function set($bit)
    {
        $offsetByte = $this->offsetToByte($bit);
        $byte = ord($this->bytes[$offsetByte]);

        $byte |= 1 << $bit % 8;
        $this->bytes[$offsetByte] = chr($byte);
    }

    /**
     * @param int $value
     */
    private function assertOffset($value)
    {
        if (!is_numeric($value)) {
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
        $byte = $offset >> 0x3;

        if ($this->size <= $byte) {
            $this->bytes .= str_repeat(chr(0), $byte - $this->size + self::DEFAULT_BYTE_SIZE);
            $this->size = strlen($this->bytes);
        }

        return $byte;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->bytes;
    }
}
