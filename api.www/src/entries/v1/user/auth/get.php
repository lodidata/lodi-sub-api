<?php
use Utils\Www\Action;


return new class extends Action
{
    const TOKEN = true;
    const TITLE = "获取TOKEN验证是否登录";
    const TAGS = "登录注册";
    public function run() {
        return $this->auth->verfiyToken();
    }
};