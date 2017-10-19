<?php

namespace RocketLabs\BloomFilter\Persist;

use RocketLabs\BloomFilter\Exception\InvalidCounter;

/**
 * @author Igor Veremchuk igor.veremchuk@rocket-internet.de
 */
class CountRedis implements CountPersister
{
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 6379;
    const DEFAULT_DB = 0;
    const DEFAULT_KEY = 'counting_bloom_filter';
    /** @var string */
    protected $key;
    /** @var \Redis */
    protected $redis;

    /**
     * @param array $params
     * @return CountRedis
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
     * @param int $bit
     * @return int
     */
    public function decrementBit(int $bit): int
    {
        $result = $this->redis->hIncrBy($this->key, $bit, -1);
        if ($result < 0) {
            $this->redis->hSet($this->key, $bit, 0);
            throw new InvalidCounter(
                sprintf(
                    'Redis key [%s] had invalid count[%s] for the bit [%s]. Has been set to 0',
                    $this->key,
                    $result,
                    $bit
                )
            );
        }

        return max([0, $result ]);
    }

    /**
     * @param int $bit
     * @return int
     */
    public function incrementBit(int $bit): int
    {
        return $this->redis->hIncrBy($this->key, $bit, 1);
    }

    /**
     * @param array $bits
     * @return array
     */
    public function incrementBulk(array $bits): array
    {
        $pipe = $this->redis->pipeline();

        $result = [];

        foreach ($bits as $bit) {
            $result[$bit] = $pipe->hIncrBy($this->key, $bit, 1);
        }

        $pipe->exec();

        return $result;
    }

    /**
     * @param int $bit
     * @return int
     */
    public function get(int $bit): int
    {
        return $this->redis->hGet($this->key, $bit);
    }
}