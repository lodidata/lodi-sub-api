<?php
use Utils\Www\Action;
use Model\Advert;

return new class extends Action {
    const HIDDEN = true;
    const TITLE = "启动后配置参数";
    const DESCRIPTION = "启动后配置参数";
    const TYPE = "";
    const SCHEMAS = [
        "qq"        => "string(required) #QQ",
        "qq_quan"   => "string(required) #qq_quan",
        "wechat"    => "string(required) #Wechat"
    ];


    public function run() {
        $app_config= (array)\DB::table('app_kf')
            ->select('qq','qq_quan','wechat')
            ->first();
        return $app_config ? $app_config : ['qq'=>'','qq_quan'=>'','wechat'=>''];
    }
};