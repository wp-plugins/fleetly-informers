<?php
/**
 * Custom cache
 */

namespace informers\client;

require_once "Cache.php";

class CustomCache implements Cache
{

    /**
     * @var \Closure
     */
    private $getCallback;

    /**
     * @var \Closure
     */
    private $setCallback;

    /**
     * @var integer - seconds
     */
    private $period;

    /**
     * @var string
     */
    private $cachePrefix;

    /**
     * @param callable $getCallback
     * @param callable $setCallback
     * @param int $period
     * @param string $cachePrefix
     */
    public function __construct(\Closure $getCallback, \Closure $setCallback, $period=86400, $cachePrefix = '')
    {
        $this->getCallback = $getCallback;
        $this->setCallback = $setCallback;
        $this->cachePrefix = $cachePrefix;
        $this->period = $period;

    }

    /**
     * Get value from cache if it not expired
     * @param $key
     * @return mixed
     */
    function get($key)
    {
        return call_user_func_array($this->getCallback, array(
            $this->getKey($key),
        ));
    }

    /**
     * Set value to cache using caching period
     * @param $key
     * @param $value
     * @return int
     */
    function set($key, $value)
    {
        return call_user_func_array($this->setCallback, array(
            $this->getKey($key),
            $value,
            $this->period,
        ));
    }

    /**
     * @param $key
     * @return string
     */
    private function getKey($key)
    {
        return $this->cachePrefix . $key;
    }
}