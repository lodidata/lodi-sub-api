<?php

function is_json($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

//求二维数组的差集|比较二维数组的不同
function array_diff_assoc2_deep($array1, $array2) {
    $ret = array();
    foreach ($array1 as $k => $v) {
        if (!isset($array2[$k])) $ret[$k] = $v;
        else if (is_array($v) && is_array($array2[$k])) $ret[$k] = array_diff_assoc2_deep($v, $array2[$k]);
        else if ($v !=$array2[$k]) $ret[$k] = $v;
        else
        {
            unset($array1[$k]);
        }

    }
    return $ret;
}

/**
 * 编辑器图片替换
 * @param $content
 * @return string|string[]|null
 */
function mergeImageUrl($content)
{
    global $app;
    $picDoman = $app->getContainer()->get('settings')['upload']['dsn'][$app->getContainer()->get('settings')['upload']['useDsn']]['domain'];
    $pregRule = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?>/";
    $content = str_replace($picDoman, '', $content);
    $content = preg_replace($pregRule, '<img src="' . $picDoman . '${1}" style="max-width:100%">', $content);
    return $content;
}

/**
 * 替换图片,返回相对地址
 * @param $content
 * @return string
 */
function replaceImageUrl($content)
{
    global $app;
    $picDoman = $app->getContainer()->get('settings')['upload']['dsn'][$app->getContainer()->get('settings')['upload']['useDsn']]['domain'];
    if (empty($content)) {
        return $content;
    }

    return str_replace($picDoman, '', $content);
}

/**
 * 完整图片地址示
 * @param $content
 * @param bool $is_more 是否多图片连接方式
 * @return string
 */
function showImageUrl($content, $is_more = false)
{
    global $app;
    if (empty($content)) {
        return $content;
    }
    if (is_string($content) && stripos($content, 'http') === 0) {
        return $content;
    }
    $picDoman = $app->getContainer()->get('settings')['upload']['dsn'][$app->getContainer()->get('settings')['upload']['useDsn']]['domain'];
    if($is_more){
        $is_array = true;
        if(!is_array($content)){
            $contents = explode(',', $content);
            $is_array = false;
        }
        foreach($contents as &$sub){
            $sub = $picDoman . '/' . ltrim($sub, '/');
        }
        unset($sub);
        $content = $is_array ? $content : implode(',', $contents);
        return $content;
    }
    return $picDoman . '/' . ltrim($content, '/');
}