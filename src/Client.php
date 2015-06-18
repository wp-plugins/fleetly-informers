<?php

namespace informers\client;


include_once "TemplateEngine.php";

class Client
{

    private $apiKey;
    private $apiUrl;
    private $apiPort = 80;
    private $siteId;

    private $executionTime = 0.25;
    private $connectionTime = 0.1;

    private static $startTime;

    /**
     * @var Cache
     */
    private $cache;

    private static $trace = array();

    /**
     * @param $settings
     * @throws \Exception
     */
    public function __construct($settings)
    {
        self::$startTime = microtime(true);

        if(empty($settings['site_id'])
            || empty($settings['api_key'])
            || empty($settings['api_url'])
            || empty($settings['cache'])
        ) {
            throw new ClientException('Incorrect informers client configuration');
        }

        if(isset($settings['api_max_connection_time'])) {
            $this->connectionTime = $settings['api_max_connection_time'];
        }
        if(isset($settings['api_max_execution_time'])) {
            $this->executionTime = $settings['api_max_execution_time'];
        }
        if(isset($settings['api_port'])) {
            $this->apiPort = $settings['api_port'];
        }

        $this->siteId = $settings['site_id'];
        $this->apiKey = $settings['api_key'];
        $this->apiUrl = $settings['api_url'];
        $this->cache  = $settings['cache'];
    }

    /**
     * @param $templateId
     * @return string
     */
    public function getTemplate($templateId)
    {
        $key = 'tpl_' . $templateId;
        $template = $this->cache->get($key);
        if (!$template) {
            $params = array(
                'id' => $templateId,
                'site' => $this->siteId,
            );
            $template = $this->get('/template', $params);
            $this->cache->set($key, $template);
        }

        return $template;
    }

    /**
     * Render informer html-code reserved for page URL
     * Use cache
     *
     * @param $url
     * @return string
     */
    public function render($url)
    {
        $data = $this->getData($url);

        if(!empty($data['informer_id'])) {
            $engine = new TemplateEngine();

            $cacheKey = 'compiled_' . $data['informer_id'];
            $compiled = $this->cache->get($cacheKey);

            if(!$compiled) {
                $template = $this->getTemplate($data['informer_id']);

                if(empty($template['error']) && !empty($template['code']) && !empty($template['items']) && isset($template['replace'])) {
                    $compiled = $engine->compileWithItems($template['code'], $template['items'], $template['replace']);
                }
                $this->cache->set($cacheKey, $compiled);
            }

            return $engine->render($compiled, $data['params']);
        } else {
            return '';
        }
    }

    /**
     * Get informerId and link settings for URL
     * Use cache
     *
     * @param $currentUrl
     * @return string
     */
    public function getData($currentUrl)
    {
        $key = 'data_' . $currentUrl;
        $data = $this->cache->get($key);
        if (!$data) {
            $params = array(
                'url' => $currentUrl,
                'site' => $this->siteId,
            );
            $data = $this->get('/data', $params);
            $this->cache->set($key, $data);
        }

        return $data;
    }

    /**
     * Build query and execute request
     *
     * @param $action
     * @param $params
     * @return string
     */
    private function get($action, $params)
    {
        $params['sig'] = $this->signature($params);
        $queryString = http_build_query($params);
        $response = $this->execute($action . "?" . $queryString);

        self::trace("get " . $action);

        $result = json_decode($response, true);
        if(!is_array($result)) {
            $result = array();
        }
        $result['t'] = time();

        return $result;
    }


    /**
     * Send request to api
     * @param $url
     * @param string $data
     * @return mixed
     * @throws ClientException
     */
    public function execute($url, $data = '')
    {
        $errNo = null;
        $errorMessage = null;
        $closeTime = microtime(true) + $this->executionTime;

        $parsed = parse_url($this->apiUrl);
        $host = $parsed['host'];
        $path = $parsed['path'];

        $fp = @fsockopen($host, $this->apiPort, $errNo, $errorMessage, $this->connectionTime);
        if (!$fp) {
            throw new ClientException("Connection error: " . $errorMessage);
        } else {

            $endOfLine = "\r\n";

            $send  = 'GET ' . $path . $url . ' HTTP/1.0' . $endOfLine;
            $send .= 'Content-Type: application/x-www-form-urlencoded' . $endOfLine;
            $send .= 'Host: ' . $host . $endOfLine;
            $send .= 'Accept: text/json' . $endOfLine;
            $send .= 'Content-Length: ' . strlen($data) . $endOfLine . $endOfLine;

            fwrite($fp, $send);

            self::trace('send '. $url);

            $body = '';
            $header = '';
            if (microtime(true) >= $closeTime) {
                throw new ClientException("Timeout was reached (1)");
            }

            do {
                if (microtime(true) >= $closeTime) {
                    throw new ClientException("Timeout was reached (2)");
                }
                $header .= fgets($fp, 1024);
            } while (strpos($header, $endOfLine . $endOfLine) === false);

            while (!feof($fp)) {
                if (microtime(true) >= $closeTime) {
                    throw new ClientException("Timeout was reached (3)");
                }
                $body .= fgets($fp);
            }
            fclose($fp);
        }

        return $body;
    }



    /**
     * Calculate request signature
     *
     * @param $params
     * @return string
     */
    private function signature($params)
    {
        ksort($params);

        $toHash = array();
        foreach ($params as $key => $value) {
            $toHash[] = $key . "=" . $value;
        }

        return md5(implode("", $toHash) . $this->apiKey);
    }

    /**
     * Add message to trace array
     * @param $message
     */
    public static function trace($message)
    {
        $time = round((microtime(true) - self::$startTime) * 1000);
        self::$trace[] = "Informers: " . $time . " " . $message;
    }

    /**
     * @return array trace
     */
    public static function getTrace()
    {
        return self::$trace;
    }

}