<?php

namespace informers\client;


require_once "Cache.php";

class FileCache implements Cache
{

    /**
     * Base path to saving cache folders and files
     * @var string
     */
    private $path;

    /**
     * expire period (sec)
     * @var int
     */
    private $period;

    /**
     * Store keys
     */
    const KEY_EXPIRE   = 'e';
    const KEY_CONTENT  = 'c';

    /**
     * @param $path
     * @param $period
     */
    public function __construct($path = 'caching', $period = 86400)
    {
        $this->path = $path;
        $this->period = $period;
    }

    /**
     * @inheritdoc
     */
    function get($key)
    {
        $real = $this->real($key);

        $fileName = $real['file'];
        if (file_exists($fileName) && is_readable($fileName)) {
            $content = json_decode(file_get_contents($fileName), true);
            if (!isset($content[self::KEY_EXPIRE]) || $content[self::KEY_EXPIRE] < time()) {
                return null;
            }
            return isset($content[self::KEY_CONTENT]) ? $content[self::KEY_CONTENT] : '';
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    function set($key, $value)
    {
        $real = $this->real($key);
        $realPath = $real['path'];

        if (!is_dir($realPath)) {
            mkdir($realPath, 755, true);
        }

        $content = json_encode(array(
            self::KEY_EXPIRE   => time() + $this->period,
            self::KEY_CONTENT  => $value
        ));

        return file_put_contents($real['file'], $content);
    }

    /**
     * @param $key
     * @return array [
     *      'file' => <full full cache file name>,
     *      'path' => <path to cache file>
     * ]
     *
     */
    private function real($key)
    {
        $realKey = md5($key);
        $keysArray = str_split($realKey, 2);

        $realPath = $this->path . DIRECTORY_SEPARATOR
            . $keysArray[0] . DIRECTORY_SEPARATOR
            . $keysArray[1];

        return array(
            'path' => $realPath,
            'file' => $realPath . DIRECTORY_SEPARATOR . $realKey,
        );
    }
}