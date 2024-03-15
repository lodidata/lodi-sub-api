<?php
use Utils\Www\Action;
use Model\Advert;
use Model\AppBag;
use Model\User;

return new class extends Action {
    const TITLE = "当前服务器时间";
    const DESCRIPTION = "当前服务器时间";
    const TAGS = '公共分类';
    const SCHEMAS = [
        "time"                  => "datetime() #当前时间 2022-02-24 18：47：21",
        'strtime'               => 'string() #当前时间截',
        'utc'                   => 'string() #当前时区'
    ];


    public function run() {
        $data['time']     = date('Y-m-d H:i:s');
        $data['strtime'] = time();
        $data['utc'] = date('P');
        return $data;
    }
};