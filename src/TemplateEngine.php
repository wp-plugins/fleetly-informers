<?php

namespace informers\client;


/**
 * Render informer templates
 * @package common\components
 */
class TemplateEngine
{

    /**
     * Blocks used while rendering blocks
     * @var array
     */
    private static $usedBlocksNumbers;


    public function __construct()
    {
        $this->resetUsedBlocks();
    }


    /**
     * @param $template
     * @param $items
     * @param array $replace
     * @return mixed
     */
    public function compileWithItems($template, $items, $replace = array())
    {
        $firstBlock = '<?php $p = ' . var_export($replace, true) . ';
            $p["item"] = ' . var_export($items, true) . ';
            $params = array_replace_recursive($p, $params); ?>';
        $secondBlock = '<?php $replace = function($params, $blockName, $key, $var, $empty)
            {
                if( isset($params[$blockName][$var]) && is_string( $params[$blockName][$var] )) {
                    return $params[$blockName][$var];
                }
                if(empty($params[$blockName][$key][$var])) {
                    if(empty($params[$blockName][0][$var])) {
                        return $empty;
                    } else {
                        $key = 0;
                    }
                }
                $paramValue = $params[$blockName][$key][$var];
                return preg_replace_callback("~%(\w+?)\.(\w+?)%~is", function($a) use ($params, $key) {
                    if(isset( $params[ $a[1] ][ $key ][  $a[2] ])) {
                        return $params[ $a[1] ][ $key ][ $a[2] ];
                    } elseif(isset( $params[ $a[1] ][  $a[2] ])) {
                        return $params[ $a[1] ][  $a[2] ];
                    } else {
                        return "";
                    }
                }, $paramValue);
            }; ?>
        ';
        $thirdBlock = $this->processBlocks($template);
        $template = preg_replace("~[\s|\\n|\\r]+~", " ", $firstBlock . $secondBlock . $thirdBlock);

        return $template;
    }

    /**
     * Render php code with params
     *
     * @param $compiledPhpCode
     * @param $params
     * @return bool
     */
    public function render($compiledPhpCode, $params)
    {

        ob_start();

        /* @var array $params - use during eval() */
        eval("?> " . $compiledPhpCode . "<?php ");

        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }


    /**
     * Find and parse one block
     *
     * @param $template
     * @param $blockName
     * @return mixed
     */
    public function processBlock($template, $blockName)
    {

        $key = self::$usedBlocksNumbers[$blockName] =
            isset(self::$usedBlocksNumbers[$blockName]) ? self::$usedBlocksNumbers[$blockName] + 1 : 0;

        $expr = '~{' . $blockName . '\.(\w+)}(.*?){\/' . $blockName . '\.(\w+)}~is';
        $obj = $this;
        return preg_replace_callback($expr, function ($data) use ($key, $blockName, $obj) {

            if ($data[1] == $data[3]) {
                return $obj->replace($data, $blockName, $key);
            } else {
                throw new ClientException('Process block error ' . serialize(array($data[1], $data[3])));
            }

        }, $template);

    }

    /**
     * @param $data
     * @param $blockName
     * @param $key
     * @return string
     */
    public function replace($data, $blockName, $key)
    {
        if ($data[1] == 'follow') {
            return '<?php
            if( empty( $params[\'' . $blockName . '\'][0]["follow"])
                && empty( $params[\'' . $blockName . '\'][' . $key . ']["follow"] )) {
                echo "rel=\"nofollow\"";
            }
            ?>';
        }
        $emptyResponse = ($data[1] == 'url') ? 'javascript:void(0);' : $data[2];

        return '<?php if(isset( $params[\'' . $blockName . '\'])) {
                echo $replace($params, \'' . $blockName . '\', ' . $key . ', \'' . $data[1] . '\', \'' . $emptyResponse . '\');
            } else {
                echo \'' . $data[2] . '\';
            } ?>';

    }

    /**
     * Find and parse blocks like:
     *   {$var:begin}
     *      {var.name} Default name {/var.name}
     *      {var.description} Default description  {/var.description}
     *   {$var:end}
     *
     * @param $template
     * @return mixed
     */
    private function processBlocks($template)
    {
        $expr = '~{\$(\w+):begin}(.+?){\$(\w+):end}~is';

        $obj = $this;
        return preg_replace_callback($expr, function ($data) use($obj) {
            if ($data[1] == $data[3]) {
                $blockName = $data[1];
                return $obj->processBlock($data[2], $blockName);
            } else {
                throw new ClientException('Process blocks error ' . serialize(array($data[1], $data[3])));
            }

        }, $template);
    }

    /**
     * Reset blocks used while rendering
     */
    public function resetUsedBlocks()
    {
        self::$usedBlocksNumbers = array();
    }

    /**
     * Count used blocks
     * @param $blockName
     * @return int
     */
    public function count($blockName)
    {
        if(isset(self::$usedBlocksNumbers[$blockName])) {
            return self::$usedBlocksNumbers[$blockName] + 1;
        }

        return 0;
    }

}
