<?php
namespace Utils;
/**
 * 小飞机警告消息
 * 第一步：@botFather 设置机器人名称，获取机器人TOKEN
 * 第二步：https://api.telegram.org/bot{TOKEN}/getUpdates 获取群消息中的chat:id 群组ID
 * 第三步：https://api.telegram.org/bot{TOKEN}/sendMessage 发消息
 * Class Telegram
 * @package Utils
 */
class Telegram
{
    /**
     * AWS小飞机告警消息(网站错误警告群)
     * @param string $content 消息内容
     */
    public static function sendMiddleOrdersMsg($content)
    {
        if(RUNMODE=='dev'){
            return true;
        }
        global $app;
        $ci = $app->getContainer();
        //防止重复
        $cache_key = \Logic\Define\CacheKey::$perfix['middle_order_message'];
        $cache_str = $ci->redis->get($cache_key);
        $content_arr = explode('警告时间',$content);
        if($cache_str == $content_arr[0]){
            return true;
        }
        $ci->redis->setex($cache_key, 300, $content_arr[0]);

        $url  = "https://api.telegram.org/bot5934472421:AAGd1u0DfH9zLWmezBW4akCOiFDEBqH61eY/sendMessage";
        $json = [
            'chat_id' => '-923153848',
            "text"    => $content,
        ];

        \Utils\Curl::post($url, null, $json);
    }
}