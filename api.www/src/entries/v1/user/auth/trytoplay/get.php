<?php
/**
 * 试玩用户注册时判断是否弹窗图片验证码
 * @author Taylor 2019-01-05
 */
use Utils\Www\Action;
return new class extends Action {
    const HIDDEN = true;
    const TITLE = "试玩游戏-获取图型验证码";
    const DESCRIPTION = "获取图型验证码，如果token和images为空串，则表示不需要使用图片验证码直接post 试玩接口进行注册登录";
    const TAGS = "登录注册";
    const SCHEMAS = [
        "token" => "string #提交验证码的时候,token也要传过去",
        "images" => "string #图片的base64编码"
    ];

    public function run() {
        $key = \Logic\Define\CacheKey::$perfix['tryToPlay']. \Utils\Client::getIp();//trytoplay:47.52.61.245
        if ($this->redis->get($key) >= 5) {
            return (new \Logic\Captcha\Captcha($this->ci))->getImageCode();
        } else {
            return ['token' => '', 'images' => ''];
        }
    }
};
