<?php
use Utils\Www\Action;
/**
 * 图形验证码
 */
return new class extends Action {
    const TITLE = "获取图型验证码";
    const DESCRIPTION = "获取图型验证码";
    const TAGS = '公共分类';
    const SCHEMAS = [
           "token" => "string(required)#(提交验证码的时候,token也要传过去)",
           "images" => "string(required)#图片的base64编码"
   ];

    public function run() {
        $res = (new \Logic\Captcha\Captcha($this->ci))->getImageCode();
        //var_dump($res);die;
        return $res;
    }
};