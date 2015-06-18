<?php

namespace informers\client;


interface Cache
{
    /**
     * Get value from cache if it not expired
     * @param $key
     * @return mixed
     */
    function get($key);

    /**
     * Set value to cache using caching period
     * @param $key
     * @param $value
     * @return int
     */
    function set($key, $value);
}