<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/4/8
 * Time: 13:41
 */

function createRsponse($response, $status = 200, $state = 0, $message = 'ok', $data = null, $attributes = null) {

    return $response
        // ->withHeader('Access-Control-Allow-Origin', '*')
        // ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        // ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withStatus($status)
        ->withJson([
            'data'       => $data,
            'attributes' => $attributes,
            'state'      => $state,
            'message'    => $message,
            'ts'         => time(),
        ]);
}

/**
 * 判断值是否是大于0的正整数
 *
 * @param $value
 *
 * @return bool
 */
function isPositiveInteger($value) {
    if (is_numeric($value) && is_int($value + 0) && ($value + 0) > 0) {
        return true;
    } else {
        return false;
    }
}


/**
 * 使用正则验证数据
 *
 * @access public
 *
 * @param string $value
 *            要验证的数据
 * @param string $rule
 *            验证规则
 *
 * @return boolean
 */
function regex($value, $rule) {

    $validate = [
        'require'      => '/\S+/',
        'email'        => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
//        'mobile'       => '/^((13[0-9]{1})|(14[5,7,8,9]{1})|(15[0-35-9]{1})|(166)|(17[01234678]{1})|(18[0-9]{1})|(19[89]{1}))\d{8}$/',
        //'mobile'       => '/^1[3456789]{1}\d{9}$/',
        'mobile'       => '/\d{6,16}$/',
        'phone'        => '/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/',
        'url'          => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
        'currency'     => '/^\d+(\.\d+)?$/',
        'number'       => '/^\d+$/',
        'zip'          => '/^\d{6}$/',
        'integer'      => '/^[-\+]?\d+$/',
        'double'       => '/^[-\+]?\d+(\.\d+)?$/',
        'english'      => '/^[A-Za-z]+$/',
        'bankcard'     => '/^\d{14,19}$/',
        'safepassword' => '/^(?=.*\\d)(?=.*[a-z])(?=.*[A-Z]).{8,20}$/',
        'chinese'      => '/^[\x{4e00}-\x{9fa5}]+$/u',
        'oddsid'       => '/^([+]?\d+)|\*$/',//验证赔率设置id
        'qq'           => '/^[1-9]\\d{4,14}/',//验证qq格式
    ];
    // 检查是否有内置的正则表达式
    if (isset ($validate [strtolower($rule)])) {
        $rule = $validate [strtolower($rule)];
    }

    return 1 === preg_match($rule, $value);
}

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
 * 完整图片地址显示
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
        $content = $is_array ? $content : implode(',',$contents);
        return $content;
    }
    return $picDoman . '/' . ltrim($content, '/');
}
