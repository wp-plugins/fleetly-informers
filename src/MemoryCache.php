<?php

namespace informers\client;


require_once "Cache.php";

class MemoryCache implements Cache
{
    /**
     * Cache instance
     * @var \Memcache
     */
    private $cache;

    /**
     * Expire period (sec)
     * @var int
     */
    private $period;

    /**
     * Cache prefix
     * @var string
     */
    private $cachePrefix = 'informers';


    /**
     * @param $cache
     * @param $period
     * @param string $cachePrefix
     * @throws \Exception
     */
    public function __construct($cache, $period, $cachePrefix = 'informers')
    {
        $this->period = $period;
        $this->cachePrefix = $cachePrefix;

        if($cache instanceof \Memcache) {
            $this->cache = $cache;
        } elseif(is_array($cache)) {
            $this->cache = new \Memcache();
            $this->cache->addserver($cache['host'], $cache['port']);
        } else {
            throw new ClientException('Incorrect cache configuration');
        }
    }

    /**
     * @inheritdoc
     */
    function get($key)
    {
        return $this->cache->get($this->real($key));
    }

    /**
     * @inheritdoc
     */
    function set($key, $value)
    {
        return $this->cache->set($this->real($key), $value, MEMCACHE_COMPRESSED, time() + $this->period);
    }

    /**
     * Builds real cache key
     * @param $key
     * @return string
     */
    public function real($key)
    {
        return $this->cachePrefix . "_" .$key;
    }
}