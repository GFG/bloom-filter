<?php

namespace RocketLabs\BloomFilter\Persist;

use RocketLabs\BloomFilter\Exception\InvalidValue;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
class BitRedis implements BitPersister
{
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 6379;
    const DEFAULT_DB = 0;
    const DEFAULT_KEY = 'bloom_filter';
    /** @var string */
    protected $key;
    /** @var \Redis */
    protected $redis;

    /**
     * @param array $params
     * @return BitRedis
     */
    public static function create(array $params = [])
    {
        $redis = new \Redis();

        $host = isset($params['host']) ? $params['host'] : self::DEFAULT_HOST;
        $port = isset($params['port']) ? $params['port'] :self::DEFAULT_PORT;
        $db = isset($params['db']) ? $params['db'] : self::DEFAULT_DB;
        $key = isset($params['key']) ? $params['key'] : self::DEFAULT_KEY;

        $redis->connect($host, $port);
        $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        $redis->select($db);

        return new self($redis, $key);
    }

    /**
     * @param \Redis $redis
     * @param string $key
     */
    public function __construct(\Redis $redis, $key)
    {
        $this->key = $key;
        $this->redis = $redis;
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        $this->redis->del($this->key);
    }

    /**
     * @inheritdoc
     */
    public function getBulk(array $bits): array
    {
        $pipe = $this->redis->pipeline();

        foreach ($bits as $bit) {
            $this->assertOffset($bit);
            $pipe->getBit($this->key, $bit);
        }
        $return = $pipe->exec();

        return is_array($return) ? $return : [$return];
    }

    /**
     * @inheritdoc
     */
    public function setBulk(array $bits)
    {
        $pipe = $this->redis->pipeline();

        foreach ($bits as $bit) {
            $this->assertOffset($bit);
            $pipe->setBit($this->key, $bit, 1);
        }

        $pipe->exec();
    }

    /**
     * @inheritdoc
     */
    public function unsetBulk(array $bits)
    {
        $pipe = $this->redis->pipeline();

        foreach ($bits as $bit) {
            $this->assertOffset($bit);
            $pipe->setBit($this->key, $bit, 0);
        }

        $pipe->exec();
    }

    /**
     * @inheritdoc
     */
    public function unset(int $bit)
    {
        $this->assertOffset($bit);
        $this->redis->setBit($this->key, $bit, 0);
    }

    /**
     * @inheritdoc
     */
    public function get(int $bit): int
    {
        $this->assertOffset($bit);
        return $this->redis->getBit($this->key, $bit);
    }

    /**
     * @inheritdoc
     */
    public function set(int $bit)
    {
        $this->assertOffset($bit);
        $this->redis->setBit($this->key, $bit, 1);
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


}